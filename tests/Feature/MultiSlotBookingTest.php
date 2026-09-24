<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Room;
use App\Models\SessionCreditLot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiSlotBookingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $this->travelTo(now()->setDate(2026, 9, 24)->setTime(8, 0));
        Mail::fake();
        config(['lock.provider' => 'simulated']);
        $room = Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);
        OpeningHour::create(['room_id' => $room->id, 'weekday' => 5, 'opens_at' => '06:00', 'closes_at' => '22:00', 'is_active' => true]);

        return ['room_id' => $room->id, 'slots' => ['2026-09-25 10:00:00', '2026-09-25 11:00:00', '2026-09-25 15:00:00'], 'booking_type' => 'single_hour', 'customer_name' => 'Test', 'customer_email' => 'test@example.test', 'bringing_children' => '0', 'terms_accepted' => '1', 'age_authorization_accepted' => '1'];
    }

    public function test_consecutive_and_separate_hours_use_one_credit_each(): void
    {
        $data = $this->payload();
        $user = User::factory()->create(['session_credits' => 3]);
        $this->actingAs($user)->post(route('bookings.store'), $data)->assertRedirect(route('account.dashboard'));
        $this->assertSame(0, $user->fresh()->session_credits);
        $this->assertDatabaseCount('bookings', 3);
        $this->assertSame(3, Booking::where('status', 'confirmed')->where('paid_with', Booking::PAID_WITH_CREDITS)->count());
        $this->assertDatabaseCount('access_codes', 3);
        Mail::assertSentCount(3);
    }

    public function test_resubmitting_batch_does_not_spend_credits_again(): void
    {
        $data = $this->payload();
        $user = User::factory()->create(['session_credits' => 6]);
        $this->actingAs($user)->post(route('bookings.store'), $data)->assertRedirect(route('account.dashboard'));
        $this->post(route('bookings.store'), $data)->assertSessionHasErrors('slots');
        $this->assertSame(3, $user->fresh()->session_credits);
        $this->assertDatabaseCount('bookings', 3);
        Mail::assertSentCount(3);
    }

    public function test_members_see_multi_select_but_guests_keep_single_slot_selection(): void
    {
        $this->payload();
        $this->get('/book?date=2026-09-25')->assertOk()->assertSee('name="starts_at"', false)->assertDontSee('name="slots[]"', false);
        $this->actingAs(User::factory()->create())->get('/book?date=2026-09-25')->assertOk()->assertSee('name="slots[]"', false)->assertSee('data-slot-summary', false);
    }

    public function test_insufficient_credit_rolls_back_all_bookings_and_sends_no_mail(): void
    {
        $data = $this->payload();
        $user = User::factory()->create(['session_credits' => 2]);
        $this->actingAs($user)->post(route('bookings.store'), $data)->assertSessionHasErrors('slots');
        $this->assertSame(2, $user->fresh()->session_credits);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('access_codes', 0);
        Mail::assertNothingSent();
    }

    public function test_busy_last_slot_rolls_back_prior_slots_and_credits(): void
    {
        $data = $this->payload();
        Booking::create(['room_id' => $data['room_id'], 'customer_name' => 'Other', 'customer_email' => 'other@example.test', 'starts_at' => $data['slots'][2], 'ends_at' => '2026-09-25 16:00:00', 'status' => 'confirmed', 'payment_status' => 'paid', 'price_cents' => 0, 'currency' => 'EUR', 'seats_reserved' => 5]);
        $user = User::factory()->create(['session_credits' => 3]);
        $this->actingAs($user)->post(route('bookings.store'), $data)->assertStatus(422);
        $this->assertSame(3, $user->fresh()->session_credits);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('access_codes', 0);
        Mail::assertNothingSent();
    }

    public function test_membership_and_pack_credits_can_cover_one_batch(): void
    {
        $data = $this->payload();
        $user = User::factory()->create(['session_credits' => 2, 'membership_credits' => 1, 'membership_expires_at' => now()->addDays(30)]);
        $this->actingAs($user)->post(route('bookings.store'), $data)->assertRedirect(route('account.dashboard'));
        $this->assertSame(0, $user->fresh()->session_credits);
        $this->assertSame(0, $user->fresh()->membership_credits);
        $this->assertSame(1, Booking::where('paid_with', Booking::PAID_WITH_MEMBERSHIP)->count());
        $this->assertSame(2, Booking::where('paid_with', Booking::PAID_WITH_CREDITS)->count());
    }

    public function test_expiry_is_checked_for_each_hour_and_rolls_back_lots(): void
    {
        $data = $this->payload();
        $user = User::factory()->create(['session_credits' => 3]);
        $lot = SessionCreditLot::create(['user_id' => $user->id, 'credits_granted' => 3, 'remaining_credits' => 3, 'expires_at' => '2026-09-25 12:00:00']);
        $this->actingAs($user)->post(route('bookings.store'), $data)->assertSessionHasErrors('slots');
        $this->assertSame(3, $lot->fresh()->remaining_credits);
        $this->assertSame(3, $user->fresh()->session_credits);
        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingSent();
    }

    public function test_duplicate_slots_guests_groups_and_missing_terms_are_rejected(): void
    {
        $data = $this->payload();
        $this->post(route('bookings.store'), $data)->assertSessionHasErrors('slots');
        $user = User::factory()->create(['session_credits' => 10]);
        $this->actingAs($user)->post(route('bookings.store'), array_replace($data, ['slots' => [$data['slots'][0], $data['slots'][0]]]))->assertSessionHasErrors('slots.0');
        $this->post(route('bookings.store'), array_replace($data, ['booking_type' => 'group_hour']))->assertSessionHasErrors('slots');
        unset($data['terms_accepted']);
        $this->post(route('bookings.store'), $data)->assertSessionHasErrors('terms_accepted');
        $this->assertDatabaseCount('bookings', 0);
    }
}
