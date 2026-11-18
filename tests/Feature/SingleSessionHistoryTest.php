<?php

namespace Tests\Feature;

use App\Models\AccessCode;
use App\Models\Booking;
use App\Models\CreditMovement;
use App\Models\Room;
use App\Models\User;
use App\Services\SingleSessionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleSessionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function booking(array $attributes = []): Booking
    {
        $user = User::factory()->create(['session_credits' => 7]);
        $room = Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR']);

        return Booking::create(array_merge(['user_id' => $user->id, 'room_id' => $room->id,
            'customer_name' => $user->name, 'customer_email' => $user->email,
            'booking_type' => 'single_hour', 'paid_with' => 'payment', 'payment_status' => 'paid',
            'status' => 'confirmed', 'confirmed_at' => now()->subDays(2),
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'price_cents' => 800, 'currency' => 'EUR'], $attributes));
    }

    public function test_paid_session_records_purchase_and_use_once_without_changing_wallet(): void
    {
        $booking = $this->booking();
        app(SingleSessionHistory::class)->record($booking);
        $this->artisan('credits:backfill-single-sessions')->assertSuccessful();
        $this->assertDatabaseCount('credit_movements', 2);
        $this->assertDatabaseHas('credit_movements', ['booking_id' => $booking->id, 'reason' => 'single_purchase', 'amount' => 1, 'balance' => 1]);
        $this->assertDatabaseHas('credit_movements', ['booking_id' => $booking->id, 'reason' => 'booking', 'amount' => -1, 'balance' => 0]);
        $this->assertSame(7, $booking->user->fresh()->session_credits);
        $this->assertEquals($booking->confirmed_at, CreditMovement::first()->created_at);
    }

    public function test_backfill_restores_history_without_changing_booking_or_credits(): void
    {
        $booking = $this->booking();
        CreditMovement::query()->delete();
        $this->artisan('credits:backfill-single-sessions')->assertSuccessful();
        $this->assertDatabaseCount('credit_movements', 2);
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(7, $booking->user->fresh()->session_credits);
    }

    public function test_unpaid_and_credit_covered_bookings_do_not_get_single_purchase_entries(): void
    {
        $pending = $this->booking(['payment_status' => 'pending', 'status' => 'pending', 'confirmed_at' => null]);
        $this->booking(['paid_with' => 'credits', 'price_cents' => 0]);
        $this->booking(['paid_with' => 'membership', 'price_cents' => 0]);
        $this->assertDatabaseCount('credit_movements', 0);
        $pending->update(['status' => 'confirmed', 'payment_status' => 'paid', 'confirmed_at' => now()]);
        $this->assertDatabaseCount('credit_movements', 2);
    }

    public function test_admin_can_view_nested_lock_payload_and_edit_code(): void
    {
        $booking = $this->booking();
        $code = AccessCode::create(['booking_id' => $booking->id, 'code' => '123456',
            'valid_from' => $booking->starts_at, 'valid_until' => $booking->ends_at,
            'provision_status' => 'failed', 'lock_response_log' => ['error' => 'Example error', 'response' => ['errcode' => 1]]]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/access-codes/'.$code->id)->assertOk()->assertSee('Example error');
        $this->get('/admin/access-codes/'.$code->id.'/edit')->assertOk();
        $code->update(['lock_response_log' => null]);
        $this->get('/admin/access-codes/'.$code->id)->assertOk();
    }
}
