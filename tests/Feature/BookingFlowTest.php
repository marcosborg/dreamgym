<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\AccessCode;
use App\Models\BlackoutPeriod;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Room;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_flow_confirms_payment_generates_code_and_sends_email(): void
    {
        Mail::fake();
        $room = $this->roomWithHours();

        $response = $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-01 10:00:00',
            'slots' => 2,
            'customer_name' => 'Sara Borges',
            'customer_email' => 'sara@example.test',
            'customer_phone' => '+351900000000',
            'create_account' => '1',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('checkout.show', $booking));
        $this->assertSame('pending', $booking->status);
        $this->assertSame('2026-06-01 11:00:00', $booking->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(2400, $booking->price_cents);
        $this->assertNotNull($booking->user_id);
        $this->assertAuthenticated();

        $this->post(route('checkout.complete', $booking))->assertRedirect(route('booking.confirmed', $booking));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $booking->accessCode->code);
        $this->assertSame('2026-06-01 09:55:00', $booking->accessCode->valid_from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-01 11:05:00', $booking->accessCode->valid_until->format('Y-m-d H:i:s'));
        $this->assertSame('provisioned', $booking->accessCode->provision_status);

        Mail::assertSent(BookingConfirmed::class);
    }

    public function test_customer_account_shows_booking_history(): void
    {
        $room = $this->roomWithHours();
        $user = \App\Models\User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => 'password',
        ]);

        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'customer_name' => 'Customer',
            'customer_email' => 'customer@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 10:30:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'price_cents' => 1200,
            'currency' => 'EUR',
        ]);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Dream Gym Private Room')
            ->assertSee('10:00');
    }

    public function test_overlapping_bookings_are_rejected(): void
    {
        $room = $this->roomWithHours();

        Booking::create([
            'room_id' => $room->id,
            'customer_name' => 'Existing',
            'customer_email' => 'existing@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 10:30:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'price_cents' => 1200,
            'currency' => 'EUR',
        ]);

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-01 10:00:00',
            'slots' => 1,
            'customer_name' => 'New',
            'customer_email' => 'new@example.test',
        ])->assertStatus(422);
    }

    public function test_blackout_periods_remove_slots_from_availability(): void
    {
        $room = $this->roomWithHours();
        BlackoutPeriod::create([
            'room_id' => $room->id,
            'title' => 'Maintenance',
            'starts_at' => '2026-06-01 11:00:00',
            'ends_at' => '2026-06-01 12:00:00',
        ]);

        $slots = app(AvailabilityService::class)->slotsForDate($room, '2026-06-01');
        $blocked = $slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '11:00');
        $open = $slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '10:30');

        $this->assertFalse($blocked['available']);
        $this->assertTrue($open['available']);
    }

    public function test_language_switch_sets_session_locale(): void
    {
        $this->get(route('locale.switch', 'en'))->assertRedirect();
        $this->withSession(['locale' => 'en'])->get(route('home'))->assertSee('Your private gym room');
    }

    private function roomWithHours(): Room
    {
        $room = Room::create([
            'name' => 'Dream Gym Private Room',
            'capacity' => 1,
            'slot_price_cents' => 1200,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        OpeningHour::create([
            'room_id' => $room->id,
            'weekday' => 1,
            'opens_at' => '09:00',
            'closes_at' => '12:00',
            'is_active' => true,
        ]);

        return $room;
    }
}
