<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Room;
use App\Models\User;
use App\Services\FounderMembershipService;
use App\Services\MembershipReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FounderMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 30)->setTime(12, 0));
        config(['payments.provider' => 'sandbox']);
    }

    public function test_price_is_personalized_without_changing_public_product_or_trusting_submitted_amount(): void
    {
        Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);
        $product = Product::where('type', 'membership')->firstOrFail();
        $product->update(['is_active' => true, 'price_cents' => 6000, 'days' => 30, 'credits' => 30]);
        $founder = User::factory()->create(['is_founder' => true]);
        $this->paid($founder, '2026-09-10 12:00:00');
        $this->actingAs($founder)->get(route('bookings.index'))->assertOk()->assertSee('40,00 EUR')->assertSee(__('site.founder_price'));
        $this->post(route('purchase.store'), ['product_id' => $product->id, 'amount_cents' => 1])->assertRedirect();
        $pending = $founder->payments()->where('status', 'pending')->firstOrFail();
        $this->assertSame(4000, $pending->amount_cents);
        $this->assertTrue($pending->metadata['founder_price']);
        $regular = User::factory()->create();
        $this->actingAs($regular)->post(route('purchase.store'), ['product_id' => $product->id, 'is_founder' => true, 'amount_cents' => 4000])->assertRedirect();
        $this->assertSame(6000, $regular->payments()->first()->amount_cents);
        $this->assertFalse($regular->fresh()->is_founder);
        $this->assertSame(6000, $product->fresh()->price_cents);
    }

    public function test_three_day_grace_keeps_price_but_does_not_extend_booking_access(): void
    {
        $user = User::factory()->create(['is_founder' => true]);
        $this->paid($user, '2026-08-30 12:00:00'); // Expires September 29.
        app(MembershipReconciliationService::class)->reconcile($user);
        $this->assertFalse($user->fresh()->hasActiveMembership());
        $this->assertTrue(app(FounderMembershipService::class)->eligible($user));
        $this->travelTo(now()->setDate(2026, 10, 2)->startOfDay());
        $this->assertTrue(app(FounderMembershipService::class)->eligible($user));
        $this->travel(1)->seconds();
        $this->assertFalse(app(FounderMembershipService::class)->eligible($user));
    }

    public function test_timely_renewals_preserve_price_and_early_renewal_extends_existing_cycle(): void
    {
        $user = User::factory()->create(['is_founder' => true]);
        $this->paid($user, '2026-08-01 12:00:00');
        $this->paid($user, '2026-08-20 12:00:00'); // extends Aug 31 through Sep 30.
        $this->assertTrue(app(FounderMembershipService::class)->eligible($user));
        $this->paid($user, '2026-09-30 11:00:00');
        $this->travelTo(now()->setDate(2026, 10, 25));
        $this->assertTrue(app(FounderMembershipService::class)->eligible($user));
    }

    public function test_a_break_permanently_loses_discount_even_after_new_membership_payment(): void
    {
        $user = User::factory()->create(['is_founder' => true]);
        $this->paid($user, '2026-08-01 12:00:00'); // Deadline September 3 00:00.
        $this->paid($user, '2026-09-10 12:00:00');
        app(MembershipReconciliationService::class)->reconcile($user);
        $this->assertTrue($user->fresh()->hasActiveMembership());
        $this->assertFalse(app(FounderMembershipService::class)->eligible($user));
        $this->paid($user, '2026-09-25 12:00:00');
        $this->assertFalse(app(FounderMembershipService::class)->eligible($user));
    }

    public function test_pending_payments_and_packs_do_not_preserve_founder_discount(): void
    {
        $user = User::factory()->create(['is_founder' => true]);
        $this->paid($user, '2026-08-01 12:00:00');
        $this->paid($user, '2026-08-20 12:00:00')->update(['status' => 'pending']);
        $this->paid($user, '2026-09-20 12:00:00')->update(['product_type' => 'session_pack']);
        $this->assertFalse(app(FounderMembershipService::class)->eligible($user));
    }

    public function test_expired_discount_cannot_be_used_by_resubmitting_an_old_checkout(): void
    {
        $user = User::factory()->create(['is_founder' => true]);
        $this->paid($user, '2026-08-01 12:00:00');
        $payment = $this->paid($user, '2026-09-01 12:00:00');
        $payment->update(['status' => 'pending', 'paid_at' => null, 'metadata' => ['credits' => 30, 'days' => 30, 'founder_price' => true]]);
        $this->actingAs($user)->post(route('purchase.complete', $payment), ['terms_accepted' => '1', 'age_authorization_accepted' => '1'])
            ->assertSessionHasErrors('payment_method');
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_admin_can_designate_founder_without_changing_customer_privileges(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $this->actingAs($admin);
        Livewire::test(EditUser::class, ['record' => $customer->id])
            ->fillForm(['is_founder' => true])->call('save')->assertHasNoFormErrors();
        $this->assertTrue($customer->fresh()->is_founder);
        $this->assertFalse($customer->fresh()->is_admin);
    }

    public function test_ifthenpay_purchase_uses_founder_price_but_packs_keep_their_price(): void
    {
        config(['payments.provider' => 'ifthenpay']);
        $room = Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);
        $product = Product::where('type', 'membership')->firstOrFail();
        $product->update(['is_active' => true, 'price_cents' => 6000, 'days' => 30, 'credits' => 30]);
        $user = User::factory()->create(['is_founder' => true]);
        $this->actingAs($user)->post(route('purchase.store'), ['product_id' => $product->id])->assertRedirect();
        $payment = $user->payments()->firstOrFail();
        $this->assertSame('ifthenpay', $payment->provider);
        $this->assertSame(4000, $payment->amount_cents);
        $this->assertTrue($payment->metadata['founder_price']);
        $pack = ['type' => 'session_pack', 'currency' => 'EUR', 'price_cents' => 4800, 'days' => 30];
        $this->assertSame(4800, app(FounderMembershipService::class)->priceFor($pack, $user)['price_cents']);
    }

    private function paid(User $user, string $at): Payment
    {
        return Payment::create(['user_id' => $user->id, 'product_type' => 'membership', 'provider' => 'manual', 'reference' => uniqid('FOUNDER-'),
            'amount_cents' => 4000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => $at, 'metadata' => ['days' => 30, 'credits' => 30]]);
    }
}
