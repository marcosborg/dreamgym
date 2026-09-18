<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\AccessCodeService;
use App\Services\MembershipReconciliationService;
use App\Services\SandboxPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ClientCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 18)->setTime(12, 0));
        Mail::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_admin_paid_membership_uses_original_date_and_edit_does_not_double_credit(): void
    {
        $user = User::factory()->create();
        $data = $this->paymentData($user);
        Livewire::test(CreatePayment::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame('2026-10-01', $user->fresh()->membership_expires_at->toDateString());
        Livewire::test(EditPayment::class, ['record' => $payment->id])->call('save')->assertHasNoFormErrors();
        $this->assertSame(30, $user->fresh()->membership_credits);
        app(SandboxPaymentService::class)->completePurchase($payment);
        $this->assertSame(30, $user->fresh()->membership_credits);
    }

    public function test_editing_pending_payment_to_paid_applies_credits_once(): void
    {
        $user = User::factory()->create();
        $payment = Payment::create(array_merge($this->paymentData($user), ['status' => 'pending']));
        Livewire::test(EditPayment::class, ['record' => $payment->id])->fillForm(['status' => 'paid'])->call('save')->assertHasNoFormErrors();
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame('2026-10-01', $user->fresh()->membership_expires_at->toDateString());
    }

    public function test_legacy_reconciliation_debits_only_eligible_bookings_and_is_repeatable(): void
    {
        $user = User::factory()->create();
        Payment::create($this->paymentData($user));
        $room = $this->room();
        $legacy = Booking::create($this->bookingData($user, $room));
        $paid = Booking::create(array_merge($this->bookingData($user, $room), ['price_cents' => 1200, 'paid_with' => 'payment']));
        $cancelled = Booking::create(array_merge($this->bookingData($user, $room), ['paid_with' => 'membership', 'user_id' => $user->id, 'status' => 'cancelled', 'cancelled_at' => now()->addHour()]));
        $service = app(MembershipReconciliationService::class);
        $first = $service->reconcile($user, true);
        $second = $service->reconcile($user, true);
        $this->assertSame($first, $second);
        $this->assertSame(29, $user->fresh()->membership_credits);
        $this->assertSame($user->id, $legacy->fresh()->user_id);
        $this->assertSame('membership', $legacy->fresh()->paid_with);
        $this->assertSame('payment', $paid->fresh()->paid_with);
    }

    public function test_imported_membership_booking_on_payment_day_is_debited_but_earlier_session_is_flagged(): void
    {
        $user = User::factory()->create();
        Payment::create($this->paymentData($user));
        $room = $this->room();
        $sameDay = Booking::create(array_merge($this->bookingData($user, $room), [
            'paid_with' => 'membership', 'payment_status' => 'pending', 'price_cents' => 4000,
            'starts_at' => '2026-09-01 06:00:00', 'ends_at' => '2026-09-01 07:00:00',
        ]));
        $sameDay->forceFill(['created_at' => '2026-09-01 08:00:00'])->save();
        $earlier = Booking::create(array_merge($this->bookingData($user, $room), [
            'price_cents' => 4000, 'starts_at' => '2026-08-31 06:00:00', 'ends_at' => '2026-08-31 07:00:00',
        ]));
        $result = app(MembershipReconciliationService::class)->reconcile($user, true);
        $this->assertSame(29, $user->fresh()->membership_credits);
        $this->assertSame('paid', $sameDay->fresh()->payment_status);
        $this->assertNull($earlier->fresh()->paid_with);
        $this->assertSame(1, $result['uncovered']);
    }

    public function test_reconciliation_preview_does_not_write(): void
    {
        $user = User::factory()->create();
        Payment::create($this->paymentData($user));
        $this->artisan('memberships:reconcile')->assertSuccessful();
        $this->assertSame(0, $user->fresh()->membership_credits);
        $this->artisan('memberships:reconcile', ['--apply' => true])->assertSuccessful();
        $this->assertSame(30, $user->fresh()->membership_credits);
    }

    public function test_admin_booking_links_customer_and_consumes_credit_without_lock_usage(): void
    {
        $user = User::factory()->create(['membership_credits' => 30, 'membership_expires_at' => now()->addDays(30)]);
        $room = $this->room();
        Livewire::test(CreateBooking::class)->fillForm($this->bookingData($user, $room))->call('create')->assertHasNoFormErrors();
        $booking = Booking::firstOrFail();
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame('membership', $booking->paid_with);
        $this->assertSame(29, $user->fresh()->membership_credits);
        Mail::assertSent(BookingConfirmed::class);
    }

    public function test_expired_credits_do_not_roll_into_a_new_membership(): void
    {
        $user = User::factory()->create();
        Payment::create(array_merge($this->paymentData($user), ['paid_at' => '2026-07-01 12:00:00']));
        $payment = Payment::create(array_merge($this->paymentData($user), ['status' => 'pending', 'paid_at' => now(), 'reference' => 'NEW']));
        app(SandboxPaymentService::class)->completePurchase($payment);
        $this->assertSame(30, $user->fresh()->membership_credits);
        $this->assertSame('2026-10-18', $user->fresh()->membership_expires_at->toDateString());
    }

    public function test_access_codes_use_only_the_six_physical_keys_and_keep_existing_codes(): void
    {
        $user = User::factory()->create();
        $room = $this->room();
        for ($i = 0; $i < 30; $i++) {
            $booking = Booking::create($this->bookingData($user, $room));
            $code = app(AccessCodeService::class)->createForBooking($booking);
            $this->assertMatchesRegularExpression('/^[1-6]{6}$/', $code->code);
            $this->assertSame($code->code, app(AccessCodeService::class)->createForBooking($booking)->code);
        }
    }

    public function test_room_translations_follow_locale_and_fallback_to_editable_name(): void
    {
        $room = $this->room();
        $room->update(['name_pt' => 'Sala de treino privado']);
        app()->setLocale('pt');
        $this->assertSame('Sala de treino privado', $room->localized_name);
        app()->setLocale('en');
        $this->assertSame('Private training room', $room->localized_name);
        $room->update(['name' => 'Studio', 'name_pt' => null]);
        app()->setLocale('pt');
        $this->assertSame('Studio', $room->localized_name);
    }

    private function room(): Room
    {
        return Room::create(['name' => 'Private training room', 'capacity' => 5, 'slot_price_cents' => 1200, 'currency' => 'EUR', 'is_active' => true]);
    }

    private function paymentData(User $user): array
    {
        return ['user_id' => $user->id, 'product_type' => 'membership', 'provider' => 'manual', 'reference' => 'TEST-'.$user->id,
            'amount_cents' => 10000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => '2026-09-01 12:00:00', 'metadata' => ['credits' => 30, 'days' => 30]];
    }

    private function bookingData(User $user, Room $room): array
    {
        return ['room_id' => $room->id, 'customer_name' => $user->name, 'customer_email' => $user->email, 'locale' => 'pt',
            'booking_type' => 'single_hour', 'seats_reserved' => 1, 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour(),
            'status' => 'confirmed', 'payment_status' => 'paid', 'price_cents' => 0, 'currency' => 'EUR', 'bringing_children' => false];
    }
}
