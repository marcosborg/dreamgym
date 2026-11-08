<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Booking;
use App\Models\CreditMovement;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\FounderMembershipService;
use App\Services\MembershipReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NovemberRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_early_renewal_resets_allowance_and_repair_is_idempotent(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 8)->setTime(12, 0));
        $user = User::factory()->create(['membership_credits' => 60, 'membership_expires_at' => '2026-12-30']);
        $this->paid($user, '2026-10-28 12:00:00');
        $this->paid($user, '2026-11-07 12:00:00');
        $this->artisan('support:repair-november')->assertSuccessful();
        $this->assertSame(60, $user->fresh()->membership_credits);
        $this->artisan('support:repair-november', ['--apply' => true])->assertSuccessful();
        $this->artisan('support:repair-november', ['--apply' => true])->assertSuccessful();
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame('2026-12-07', $user->fresh()->membership_expires_at->toDateString());
        $this->assertSame(1, CreditMovement::where('user_id', $user->id)->count());
        $user->update(['membership_credits' => 17]);
        $this->artisan('support:repair-november', ['--apply' => true])->assertSuccessful();
        $this->assertSame(17, $user->fresh()->membership_credits);
    }

    public function test_manual_membership_credits_require_a_usable_deadline(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $user = User::factory()->create(['membership_credits' => 0, 'membership_expires_at' => null]);
        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['membership_credits' => 16, 'credit_adjustment_reason' => 'Transferência'])
            ->call('save')->assertHasFormErrors(['membership_expires_at']);
        $this->assertSame(0, $user->fresh()->membership_credits);
        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['membership_credits' => 16, 'credit_adjustment_reason' => 'Transferência', 'membership_expires_at' => now()->addDays(30)->format('Y-m-d H:i:s')])
            ->call('save')->assertHasNoFormErrors();
        $this->assertTrue($user->fresh()->hasActiveMembership());
        $this->assertSame(16, $user->fresh()->membership_credits);
    }

    public function test_cancelling_a_booking_from_an_older_cycle_does_not_increase_new_allowance(): void
    {
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(12, 0));
        $user = User::factory()->create();
        $this->paid($user, '2026-11-01 10:00:00');
        $room = Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR']);
        $booking = Booking::create(['user_id' => $user->id, 'room_id' => $room->id,
            'customer_name' => $user->name, 'customer_email' => $user->email,
            'booking_type' => 'single_hour', 'paid_with' => 'membership', 'payment_status' => 'paid', 'status' => 'confirmed',
            'confirmed_at' => now(), 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHour(), 'price_cents' => 0, 'currency' => 'EUR']);
        $this->travel(5)->days();
        $this->paid($user, now()->toDateTimeString());
        app(MembershipReconciliationService::class)->reconcile($user);
        app(BookingCancellationService::class)->cancel($booking);
        $this->assertSame(30, $user->fresh()->membership_credits);
        app(MembershipReconciliationService::class)->reconcile($user);
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame(0, CreditMovement::where('reason', 'cancellation')->count());
    }

    public function test_campaign_has_no_member_limit_but_closes_for_new_members_after_december(): void
    {
        $service = app(FounderMembershipService::class);
        User::factory()->count(31)->create(['is_founder' => true]);
        $user = User::factory()->create(['is_founder' => false]);
        $this->travelTo(now()->setDate(2026, 12, 31)->setTime(23, 59, 59));
        $this->assertTrue($service->eligible($user));
        $this->paid($user, now()->toDateTimeString());
        $this->travel(1)->seconds();
        $this->assertTrue($service->eligible($user));
        $this->assertFalse($service->eligible(User::factory()->create(['is_founder' => false])));
        $this->travel(34)->days();
        $this->assertFalse($service->eligible($user));
    }

    private function paid(User $user, string $at): Payment
    {
        return Payment::create(['user_id' => $user->id, 'product_type' => 'membership', 'provider' => 'manual', 'reference' => uniqid('NOV-'),
            'amount_cents' => 4000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => $at, 'metadata' => ['days' => 30, 'credits' => 30]]);
    }
}
