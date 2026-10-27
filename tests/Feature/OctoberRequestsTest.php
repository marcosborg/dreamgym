<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Mail\BookingConfirmed;
use App\Mail\NewCustomer;
use App\Models\Booking;
use App\Models\CreditMovement;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\BookingMail;
use App\Services\MembershipReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OctoberRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 27)->setTime(12, 0));
        Mail::fake();
    }

    public function test_registration_rejects_markup_bad_phone_and_honeypot_and_notifies_admin(): void
    {
        $data = ['name' => 'Maria José', 'email' => 'maria@example.com', 'phone' => '+351 912 345 678', 'password' => 'password123', 'password_confirmation' => 'password123'];
        foreach ([['name' => '<a href="x">Spam</a>'], ['phone' => 'abcdefg'], ['website' => 'spam']] as $bad) {
            $this->post('/register', array_merge($data, $bad))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('users', 0);
        $this->post('/register', $data)->assertRedirect('/account');
        Mail::assertSent(NewCustomer::class, fn ($m) => $m->hasTo('info@dreamgym.pt'));
        $this->assertFalse(User::first()->is_admin);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [])->assertStatus(302);
        }
        $this->post('/register', [])->assertStatus(429);
    }

    public function test_twelve_hour_refund_survives_reconciliation_and_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        Payment::create(['user_id' => $user->id, 'provider' => 'manual', 'product_type' => 'membership', 'reference' => 'oct-test', 'amount_cents' => 6000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => now()->subDay(), 'metadata' => ['credits' => 30, 'days' => 30]]);
        $service = app(MembershipReconciliationService::class);
        $service->reconcile($user);
        $b = $this->booking($user);
        $service->reconcile($user);
        $this->assertSame(29, $user->fresh()->membership_credits);
        app(BookingCancellationService::class)->cancel($b);
        app(BookingCancellationService::class)->cancel($b);
        $this->assertSame(30, $user->fresh()->membership_credits);
        $service->reconcile($user);
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame(1, CreditMovement::where('reason', 'cancellation')->count());
        $this->actingAs($user)->get('/account')->assertOk()->assertSee('Cancelada em')->assertSee('Histórico de créditos');
        $other = User::factory()->create();
        $this->actingAs($other)->get('/account')->assertDontSee('Devolução por cancelamento');
    }

    public function test_late_cancellation_does_not_refund(): void
    {
        $user = User::factory()->create(['membership_credits' => 9]);
        $b = $this->booking($user);
        $b->update(['starts_at' => now()->addHours(11)]);
        app(BookingCancellationService::class)->cancel($b);
        $this->assertSame(9, $user->fresh()->membership_credits);
        $this->assertDatabaseCount('credit_movements', 0);
    }

    public function test_silent_booking_and_copy_to_admin(): void
    {
        $b = $this->booking(User::factory()->create());
        $b->update(['suppress_notifications' => true]);
        BookingMail::confirmed($b);
        Mail::assertNothingSent();
        $b->update(['suppress_notifications' => false]);
        BookingMail::confirmed($b);
        Mail::assertSent(BookingConfirmed::class, fn ($m) => $m->hasTo($b->customer_email) && $m->hasBcc('info@dreamgym.pt'));
    }

    public function test_manual_adjustment_requires_reason_and_preserves_adjustment_on_recalculation(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $user = User::factory()->create();
        Payment::create(['user_id' => $user->id, 'provider' => 'manual', 'product_type' => 'membership', 'reference' => 'manual-test', 'amount_cents' => 6000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => now()->subDay(), 'metadata' => ['credits' => 30, 'days' => 30]]);
        app(MembershipReconciliationService::class)->reconcile($user);
        Livewire::test(EditUser::class, ['record' => $user->id])->fillForm(['membership_credits' => 29])->call('save')->assertHasFormErrors();
        Livewire::test(EditUser::class, ['record' => $user->id])->fillForm(['membership_credits' => 29, 'credit_adjustment_reason' => 'Utilização sem reserva'])->call('save')->assertHasNoFormErrors();
        app(MembershipReconciliationService::class)->reconcile($user);
        $this->assertSame(29, $user->fresh()->membership_credits);
        $this->assertDatabaseHas('credit_movements', ['user_id' => $user->id, 'amount' => -1, 'reason' => 'manual:Utilização sem reserva']);
        Mail::assertNothingSent();
    }

    public function test_refund_repair_is_idempotent_and_preserves_unexplained_manual_balances(): void
    {
        $user = User::factory()->create();
        Payment::create(['user_id' => $user->id, 'provider' => 'manual', 'product_type' => 'membership', 'reference' => 'repair-test', 'amount_cents' => 6000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => now()->subDay(), 'metadata' => ['credits' => 30, 'days' => 30]]);
        $booking = $this->booking($user);
        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        app(MembershipReconciliationService::class)->reconcile($user, false, true, 24);
        $this->assertSame(29, $user->fresh()->membership_credits);
        $this->artisan('support:audit-october', ['--repair-refunds' => true])->assertSuccessful();
        $this->artisan('support:audit-october', ['--repair-refunds' => true])->assertSuccessful();
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame(1, CreditMovement::where('reason', 'refund_correction')->count());
        $user->update(['membership_credits' => 28]);
        $this->artisan('support:audit-october', ['--repair-refunds' => true])->assertSuccessful();
        $this->assertSame(28, $user->fresh()->membership_credits);
    }

    private function booking(User $u): Booking
    {
        $r = Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);

        return Booking::create(['user_id' => $u->id, 'room_id' => $r->id, 'customer_name' => $u->name, 'customer_email' => $u->email, 'booking_type' => 'single_hour', 'seats_reserved' => 1, 'starts_at' => now()->addHours(18), 'ends_at' => now()->addHours(19), 'status' => 'confirmed', 'payment_status' => 'paid', 'paid_with' => 'membership', 'price_cents' => 0, 'currency' => 'EUR', 'confirmed_at' => now()]);
    }
}
