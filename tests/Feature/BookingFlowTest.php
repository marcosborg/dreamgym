<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\AccessCode;
use App\Models\BlackoutPeriod;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-05 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_booking_flow_confirms_payment_generates_code_and_sends_email(): void
    {
        Mail::fake();
        $room = $this->roomWithHours();

        $response = $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'Sara Borges',
            'customer_email' => 'sara@example.test',
            'customer_phone' => '+351900000000',
            'bringing_children' => '0',
            'create_account' => '1',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('checkout.show', $booking));
        $this->assertSame('pending', $booking->status);
        $this->assertSame('2026-06-08 11:00:00', $booking->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(1200, $booking->price_cents);
        $this->assertNotNull($booking->user_id);
        $this->assertAuthenticated();

        $this->post(route('checkout.complete', $booking), [
            'terms_accepted' => '1',
        ])->assertRedirect(route('booking.confirmed', $booking));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $booking->accessCode->code);
        $this->assertSame('2026-06-08 09:55:00', $booking->accessCode->valid_from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-08 11:05:00', $booking->accessCode->valid_until->format('Y-m-d H:i:s'));
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
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
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

    public function test_booking_with_children_requires_responsibility_acceptance(): void
    {
        $room = $this->roomWithHours();

        $this->from(route('bookings.index'))->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'Parent',
            'customer_email' => 'parent@example.test',
            'bringing_children' => '1',
        ])->assertRedirect(route('bookings.index'))
            ->assertSessionHasErrors('children_responsibility_accepted');
    }

    public function test_booking_without_children_records_false(): void
    {
        $room = $this->roomWithHours();

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'No Children',
            'customer_email' => 'nochildren@example.test',
            'bringing_children' => '0',
        ])->assertRedirect();

        $booking = Booking::firstOrFail();

        $this->assertFalse($booking->bringing_children);
        $this->assertNull($booking->children_responsibility_accepted_at);
    }

    public function test_checkout_requires_terms_acceptance(): void
    {
        $room = $this->roomWithHours();
        $booking = Booking::create([
            'room_id' => $room->id,
            'customer_name' => 'Checkout',
            'customer_email' => 'checkout@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_with' => Booking::PAID_WITH_PAYMENT,
            'price_cents' => 1200,
            'currency' => 'EUR',
        ]);

        $this->from(route('checkout.show', $booking))
            ->post(route('checkout.complete', $booking))
            ->assertRedirect(route('checkout.show', $booking))
            ->assertSessionHasErrors('terms_accepted');
    }

    public function test_checkout_records_terms_acceptance(): void
    {
        Mail::fake();
        $room = $this->roomWithHours();
        $booking = Booking::create([
            'room_id' => $room->id,
            'customer_name' => 'Checkout',
            'customer_email' => 'checkout@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_with' => Booking::PAID_WITH_PAYMENT,
            'price_cents' => 1200,
            'currency' => 'EUR',
        ]);

        $this->post(route('checkout.complete', $booking), [
            'terms_accepted' => '1',
        ])->assertRedirect(route('booking.confirmed', $booking));

        $booking->refresh();

        $this->assertNotNull($booking->terms_accepted_at);
        $this->assertNotNull($booking->payment->terms_accepted_at);
    }

    public function test_overlapping_bookings_are_rejected(): void
    {
        $room = $this->roomWithHours();

        Booking::create([
            'room_id' => $room->id,
            'customer_name' => 'Existing',
            'customer_email' => 'existing@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'price_cents' => 1200,
            'currency' => 'EUR',
        ]);

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'New',
            'customer_email' => 'new@example.test',
            'bringing_children' => '0',
        ])->assertStatus(422);
    }

    public function test_room_capacity_allows_multiple_bookings_for_the_same_slot(): void
    {
        $room = $this->roomWithHours(capacity: 4);

        for ($i = 1; $i <= 4; $i++) {
            $this->post(route('bookings.store'), [
                'room_id' => $room->id,
                'starts_at' => '2026-06-08 10:00:00',
                'booking_type' => Booking::TYPE_SINGLE_HOUR,
                'customer_name' => "Customer {$i}",
                'customer_email' => "customer{$i}@example.test",
                'bringing_children' => '0',
            ])->assertRedirect();
        }

        $this->assertSame(4, Booking::query()->count());
        $this->assertFalse(app(AvailabilityService::class)->isAvailable(
            $room,
            Carbon::parse('2026-06-08 10:00:00', config('app.timezone')),
        ));

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'Extra Customer',
            'customer_email' => 'extra@example.test',
            'bringing_children' => '0',
        ])->assertStatus(422);
    }

    public function test_group_booking_blocks_the_full_room_and_uses_group_price(): void
    {
        $room = $this->roomWithHours(capacity: 4);

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_GROUP_HOUR,
            'customer_name' => 'Group Lead',
            'customer_email' => 'group@example.test',
            'bringing_children' => '0',
        ])->assertRedirect();

        $booking = Booking::firstOrFail();

        $this->assertSame(Booking::TYPE_GROUP_HOUR, $booking->booking_type);
        $this->assertSame(4, $booking->seats_reserved);
        $this->assertSame(10200, $booking->price_cents);

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'Late Customer',
            'customer_email' => 'late@example.test',
            'bringing_children' => '0',
        ])->assertStatus(422);
    }

    public function test_group_booking_reserves_the_full_room_as_one_booking(): void
    {
        $room = $this->roomWithHours(capacity: 5);

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_GROUP_HOUR,
            'customer_name' => 'Group Lead',
            'customer_email' => 'group-capacity@example.test',
            'bringing_children' => '0',
        ])->assertRedirect();

        $booking = Booking::firstOrFail();

        $this->assertSame(Booking::TYPE_GROUP_HOUR, $booking->booking_type);
        $this->assertSame(5, $booking->seats_reserved);
        $this->assertSame(Booking::PAID_WITH_PAYMENT, $booking->paid_with);
    }

    public function test_each_confirmed_booking_gets_its_own_access_code(): void
    {
        Mail::fake();
        $room = $this->roomWithHours(capacity: 2);

        foreach (['10:00:00', '11:00:00'] as $index => $time) {
            $this->post(route('bookings.store'), [
                'room_id' => $room->id,
                'starts_at' => "2026-06-08 {$time}",
                'booking_type' => Booking::TYPE_SINGLE_HOUR,
                'customer_name' => "Customer {$time}",
                'customer_email' => "customer-{$index}@example.test",
                'bringing_children' => '0',
            ])->assertRedirect();

            $booking = Booking::query()->latest('id')->firstOrFail();

            $this->post(route('checkout.complete', $booking), [
                'terms_accepted' => '1',
            ])->assertRedirect(route('booking.confirmed', $booking));
        }

        $codes = AccessCode::query()->orderBy('booking_id')->get();

        $this->assertCount(2, $codes);
        $this->assertNotSame($codes[0]->booking_id, $codes[1]->booking_id);
        $this->assertNotSame($codes[0]->code, $codes[1]->code);
    }

    public function test_booking_page_has_actionable_plan_cards_and_children_markup(): void
    {
        $this->roomWithHours(capacity: 5);

        $response = $this->get(route('bookings.index'));

        $response->assertOk()
            ->assertSee('data-booking-type="single_hour"', false)
            ->assertSee('data-booking-type="group_hour"', false)
            ->assertSee('data-product-id=', false)
            ->assertSee('Até 5 pessoas')
            ->assertSee('id="children-responsibility"', false)
            ->assertSee('no-underline decoration-transparent', false);
    }

    public function test_session_pack_purchase_adds_credits_and_booking_consumes_one(): void
    {
        $room = $this->roomWithHours();

        $this->post(route('purchase.store'), [
            'product_type' => 'session_pack',
            'customer_name' => 'Pack Customer',
            'customer_email' => 'pack@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect();

        $payment = Payment::firstOrFail();
        $this->post(route('purchase.complete', $payment), [
            'terms_accepted' => '1',
        ])->assertRedirect(route('purchase.confirmed', $payment));

        $user = User::firstWhere('email', 'pack@example.test');
        $this->assertSame(10, $user->fresh()->session_credits);

        $this->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'Pack Customer',
            'customer_email' => 'pack@example.test',
            'bringing_children' => '0',
            'terms_accepted' => '1',
        ])->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(Booking::PAID_WITH_CREDITS, $booking->paid_with);
        $this->assertSame(0, $booking->price_cents);
        $this->assertSame(9, $user->fresh()->session_credits);
    }

    public function test_purchase_checkout_requires_terms_acceptance(): void
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.test',
            'password' => 'password',
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'product_type' => 'session_pack',
            'provider' => 'sandbox_mbway_placeholder',
            'reference' => 'DG-TERMS',
            'amount_cents' => 10000,
            'currency' => 'EUR',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->from(route('purchase.checkout', $payment))
            ->post(route('purchase.complete', $payment))
            ->assertRedirect(route('purchase.checkout', $payment))
            ->assertSessionHasErrors('terms_accepted');
    }

    public function test_membership_purchase_allows_booking_without_hour_payment(): void
    {
        $room = $this->roomWithHours();
        $user = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password' => 'password',
            'membership_expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)->post(route('bookings.store'), [
            'room_id' => $room->id,
            'starts_at' => '2026-06-08 10:00:00',
            'booking_type' => Booking::TYPE_SINGLE_HOUR,
            'customer_name' => 'Member',
            'customer_email' => 'member@example.test',
            'bringing_children' => '0',
            'terms_accepted' => '1',
        ])->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame(Booking::PAID_WITH_MEMBERSHIP, $booking->paid_with);
        $this->assertSame(0, $booking->price_cents);
    }

    public function test_customer_cancellation_before_cutoff_returns_credit(): void
    {
        $room = $this->roomWithHours();
        $user = User::create([
            'name' => 'Customer',
            'email' => 'cancel@example.test',
            'password' => 'password',
        ]);
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'customer_name' => 'Customer',
            'customer_email' => 'cancel@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_with' => Booking::PAID_WITH_CREDITS,
            'price_cents' => 0,
            'currency' => 'EUR',
        ]);

        $this->actingAs($user)
            ->post(route('account.bookings.cancel', $booking))
            ->assertRedirect(route('account.dashboard'));

        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame(1, $user->fresh()->session_credits);
    }

    public function test_customer_cancellation_inside_cutoff_does_not_return_credit(): void
    {
        $room = $this->roomWithHours();
        $user = User::create([
            'name' => 'Customer',
            'email' => 'late-cancel@example.test',
            'password' => 'password',
        ]);
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'customer_name' => 'Customer',
            'customer_email' => 'late-cancel@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-06 10:00:00',
            'ends_at' => '2026-06-06 11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_with' => Booking::PAID_WITH_CREDITS,
            'price_cents' => 0,
            'currency' => 'EUR',
        ]);

        $this->actingAs($user)
            ->post(route('account.bookings.cancel', $booking))
            ->assertRedirect(route('account.dashboard'));

        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
        $this->assertSame(0, $user->fresh()->session_credits);
    }

    public function test_customer_cannot_cancel_another_users_booking(): void
    {
        $room = $this->roomWithHours();
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
        ]);
        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.test',
            'password' => 'password',
        ]);
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'customer_name' => 'Owner',
            'customer_email' => 'owner@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_with' => Booking::PAID_WITH_CREDITS,
            'price_cents' => 0,
            'currency' => 'EUR',
        ]);

        $this->actingAs($other)
            ->post(route('account.bookings.cancel', $booking))
            ->assertForbidden();

        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    public function test_blackout_periods_remove_slots_from_availability(): void
    {
        $room = $this->roomWithHours();
        BlackoutPeriod::create([
            'room_id' => $room->id,
            'title' => 'Maintenance',
            'starts_at' => '2026-06-08 11:00:00',
            'ends_at' => '2026-06-08 12:00:00',
        ]);

        $slots = app(AvailabilityService::class)->slotsForDate($room, '2026-06-08');
        $blocked = $slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '11:00');
        $open = $slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '10:00');

        $this->assertFalse($blocked['available']);
        $this->assertTrue($open['available']);
    }

    public function test_past_slots_are_unavailable(): void
    {
        $room = $this->roomWithHours();

        OpeningHour::query()->update([
            'weekday' => 5,
            'opens_at' => '09:00',
            'closes_at' => '15:00',
        ]);

        $slots = app(AvailabilityService::class)->slotsForDate($room, '2026-06-05');

        $past = $slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '11:00');
        $future = $slots->first(fn ($slot) => $slot['starts_at']->format('H:i') === '13:00');

        $this->assertFalse($past['available']);
        $this->assertTrue($future['available']);
    }

    public function test_language_switch_sets_session_locale(): void
    {
        $this->get(route('locale.switch', 'en'))->assertRedirect();
        $this->withSession(['locale' => 'en'])->get(route('home'))->assertSee('Your private gym room');
    }

    private function roomWithHours(int $capacity = 1): Room
    {
        $room = Room::create([
            'name' => 'Dream Gym Private Room',
            'capacity' => $capacity,
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
