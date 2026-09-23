<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\SessionCreditLot;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\SandboxPaymentService;
use App\Services\SessionCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionCreditValidityTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(User $user, int $credits = 6): Payment
    {
        $payment = Payment::create([
            'user_id' => $user->id, 'provider' => 'sandbox_mbway_placeholder',
            'product_type' => 'session_pack', 'reference' => uniqid('PACK'), 'amount_cents' => 3600,
            'currency' => 'EUR', 'status' => 'pending', 'metadata' => ['credits' => $credits, 'days' => 60],
        ]);
        app(SandboxPaymentService::class)->completePurchase($payment);

        return $payment->fresh();
    }

    public function test_each_paid_purchase_keeps_its_own_ninety_day_deadline(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 23)->setTime(12, 0));
        $user = User::factory()->create(['session_credits' => 0]);
        $first = $this->purchase($user);
        $this->travel(10)->days();
        $second = $this->purchase($user);
        app(SandboxPaymentService::class)->completePurchase($first);
        $lots = SessionCreditLot::orderBy('expires_at')->get();
        $this->assertCount(2, $lots);
        $this->assertTrue($lots[0]->expires_at->equalTo($first->paid_at->copy()->addDays(90)));
        $this->assertTrue($lots[1]->expires_at->equalTo($second->paid_at->copy()->addDays(90)));
        $this->assertSame(12, $user->fresh()->session_credits);
        DB::transaction(fn () => app(SessionCreditService::class)->consume($user->fresh(), now()->addDay()));
        $this->assertSame(5, $lots[0]->fresh()->remaining_credits);
        $this->assertSame(6, $lots[1]->fresh()->remaining_credits);
    }

    public function test_expiration_is_idempotent_and_does_not_erase_manual_credits(): void
    {
        $user = User::factory()->create(['session_credits' => 2]);
        $payment = $this->purchase($user);
        $this->travelTo($payment->paid_at->copy()->addDays(90));
        $service = app(SessionCreditService::class);
        $this->artisan('credits:expire')->assertSuccessful();
        $summary = $service->summary($user);
        $service->summary($user);
        $this->assertSame(2, $user->fresh()->session_credits);
        $this->assertSame(2, $summary['undated']);
        $this->assertSame([], $summary['dates']);
    }

    public function test_booking_after_expiry_cannot_spend_the_pack(): void
    {
        $user = User::factory()->create(['session_credits' => 0]);
        $payment = $this->purchase($user);
        $available = DB::transaction(fn () => app(SessionCreditService::class)->availableFor($user->fresh(), $payment->paid_at->copy()->addDays(90)));
        $this->assertSame(0, $available);
        $this->assertSame(6, $user->fresh()->session_credits);
    }

    public function test_cancellation_returns_credit_to_original_lot_without_extending_it(): void
    {
        $user = User::factory()->create(['session_credits' => 0]);
        $payment = $this->purchase($user);
        $lotId = DB::transaction(fn () => app(SessionCreditService::class)->consume($user->fresh(), now()->addDays(2)));
        $room = Room::create(['name' => 'Test', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR']);
        $booking = Booking::create([
            'user_id' => $user->id, 'room_id' => $room->id, 'session_credit_lot_id' => $lotId,
            'customer_name' => 'Test', 'customer_email' => 'test@example.test',
            'booking_type' => Booking::TYPE_SINGLE_HOUR, 'paid_with' => Booking::PAID_WITH_CREDITS,
            'status' => 'confirmed', 'payment_status' => 'paid', 'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(), 'price_cents' => 0, 'currency' => 'EUR',
        ]);
        app(BookingCancellationService::class)->cancel($booking);
        app(BookingCancellationService::class)->cancel($booking);
        $lot = SessionCreditLot::findOrFail($lotId);
        $this->assertSame(6, $lot->remaining_credits);
        $this->assertSame(6, $user->fresh()->session_credits);
        $this->assertTrue($lot->expires_at->equalTo($payment->paid_at->copy()->addDays(90)));
    }

    public function test_account_separates_pack_dates_and_membership_date_even_with_zero_membership_credits(): void
    {
        $user = User::factory()->create(['session_credits' => 0, 'membership_credits' => 0, 'membership_expires_at' => now()->addDays(30)]);
        $payment = $this->purchase($user);
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk()
            ->assertSee('90 dias por compra')->assertSee('30 dias por mensalidade')
            ->assertSee($payment->paid_at->copy()->addDays(90)->format('d/m/Y'))
            ->assertSee($user->membership_expires_at->format('d/m/Y'));
    }
}
