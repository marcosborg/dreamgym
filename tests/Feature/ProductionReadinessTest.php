<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\AccessCode;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\AccessCodeService;
use App\Services\AvailabilityService;
use App\Services\BookingCancellationService;
use App\Services\Payments\IfthenpayGatewayFactory;
use App\Services\Payments\IfthenpayPaymentService;
use App\Services\SandboxPaymentService;
use Ifthenpay\PaymentGateway\Enums\Status;
use Ifthenpay\PaymentGateway\IfthenpayGateway;
use Ifthenpay\PaymentGateway\Model\Mbway;
use Ifthenpay\PaymentGateway\Model\MultibancoDynamic;
use Ifthenpay\PaymentGateway\Service\MbwayService;
use Ifthenpay\PaymentGateway\Service\MultibancoDynamicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfHour());
        config(['lock.provider' => 'simulated']);
        Mail::fake();
    }

    public function test_provider_initialization_sets_deadline_and_does_not_duplicate_requests(): void
    {
        config(['payments.provider' => 'ifthenpay']);
        foreach (['mbway', 'multibanco'] as $method) {
            $booking = $this->booking();
            $service = app(IfthenpayPaymentService::class);
            $payment = $service->createPayment($booking);
            $deadline = now()->addMinutes(4)->toDateTimeImmutable();
            $model = $method === 'mbway'
                ? new Mbway('8.00', $payment->reference, 'test-id', '900000000', Status::PENDING, $deadline)
                : new MultibancoDynamic('8.00', $payment->reference, '12345', '123456789', 'test-id', Status::PENDING, $deadline);
            $gateway = \Mockery::mock(IfthenpayGateway::class);
            $methodService = \Mockery::mock($method === 'mbway'
                ? MbwayService::class
                : MultibancoDynamicService::class);
            $methodService->shouldReceive('initPayment')->once()->andReturn($model);
            $gateway->shouldReceive($method === 'mbway' ? 'mbway' : 'multibancoDynamic')->once()->andReturn($methodService);
            $this->mock(IfthenpayGatewayFactory::class)->shouldReceive('make')->once()->andReturn($gateway);
            $service = app(IfthenpayPaymentService::class);
            $service->initialize($payment, $method, '900000000');
            $service->initialize($payment, $method, '900000000');
            $this->assertTrue($booking->fresh()->payment_expires_at->equalTo($deadline));
            $this->assertSame('ifthenpay', $payment->fresh()->provider);
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertNull($booking->fresh()->accessCode);
        }
    }

    public function test_readiness_report_does_not_send_mail_by_default(): void
    {
        $this->artisan('production:readiness')->assertSuccessful();
        Mail::assertNothingSent();
    }

    private function booking(array $overrides = []): Booking
    {
        $room = Room::create(['name' => 'Audit', 'capacity' => 1, 'slot_price_cents' => 800, 'currency' => 'EUR']);

        return Booking::create(array_merge([
            'room_id' => $room->id, 'user_id' => User::factory()->create()->id,
            'customer_name' => 'Test', 'customer_email' => 'test@example.test', 'locale' => 'pt',
            'booking_type' => 'single_hour', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'status' => 'pending', 'payment_status' => 'pending', 'price_cents' => 800,
            'currency' => 'EUR', 'paid_with' => 'payment', 'seats_reserved' => 1,
        ], $overrides));
    }

    public function test_guest_and_other_customer_cannot_read_or_pay_another_booking(): void
    {
        $booking = $this->booking(['status' => 'confirmed', 'payment_status' => 'paid']);
        $code = app(AccessCodeService::class)->createForBooking($booking);
        $code->update(['provision_status' => AccessCode::PROVISIONED]);
        foreach ([null, User::factory()->create()] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            $this->get(route('booking.confirmed', $booking))->assertForbidden()->assertDontSee($code->code);
            $this->get(route('checkout.show', $booking))->assertForbidden();
            $this->post(route('checkout.complete', $booking), ['terms_accepted' => 1])->assertForbidden();
        }
        $this->actingAs($booking->user)->get(route('booking.confirmed', $booking))->assertOk()->assertSee($code->code)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_guest_can_access_only_the_booking_created_in_their_session(): void
    {
        $booking = $this->booking(['user_id' => null]);
        $other = $this->booking(['user_id' => null]);
        $this->withSession(['guest_booking_ids' => [$booking->id]])->get(route('checkout.show', $booking))->assertOk();
        $this->get(route('checkout.show', $other))->assertForbidden();
        $this->get(route('booking.confirmed', $booking))->assertNotFound();
    }

    public function test_abandoned_booking_releases_capacity_and_cannot_be_paid(): void
    {
        $booking = $this->booking();
        $booking->forceFill(['created_at' => now()->subMinutes(16)])->save();
        $this->assertFalse(app(AvailabilityService::class)->hasConflict($booking->room, $booking->starts_at, $booking->ends_at));
        $this->actingAs($booking->user)->post(route('checkout.complete', $booking), ['terms_accepted' => 1])->assertStatus(422);
        $this->get(route('checkout.show', $booking))->assertOk()->assertSee(__('site.payment_hold_expired'))->assertDontSee('action="'.route('checkout.complete', $booking).'"', false);
        $this->assertNull($booking->fresh()->accessCode);
    }

    public function test_payment_reference_holds_capacity_until_its_explicit_deadline(): void
    {
        $booking = $this->booking(['payment_expires_at' => now()->addHours(3)]);
        $booking->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->assertTrue(app(AvailabilityService::class)->hasConflict($booking->room, $booking->starts_at, $booking->ends_at));
        $this->travel(3)->hours();
        $this->assertFalse(app(AvailabilityService::class)->hasConflict($booking->room, $booking->starts_at, $booking->ends_at));
    }

    public function test_late_payment_does_not_confirm_an_occupied_slot_or_issue_a_pin(): void
    {
        $booking = $this->booking(['payment_expires_at' => now()->subMinute()]);
        $this->booking(['room_id' => $booking->room_id, 'status' => 'confirmed', 'payment_status' => 'paid']);
        $payments = app(SandboxPaymentService::class);
        $payment = $payments->createPayment($booking);
        $payments->complete($payment);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->metadata['requires_review']);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->accessCode);
        Mail::assertNothingSent();
        $this->actingAs($booking->user)->get(route('checkout.show', $booking))->assertOk()->assertSee(__('site.payment_requires_review'));
    }

    public function test_late_payment_can_confirm_when_slot_is_still_free(): void
    {
        $booking = $this->booking(['payment_expires_at' => now()->subMinute()]);
        $payments = app(SandboxPaymentService::class);
        $payment = $payments->createPayment($booking);
        $payments->complete($payment);
        $payments->complete($payment->fresh());
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, AccessCode::where('booking_id', $booking->id)->count());
        Mail::assertSent(BookingConfirmed::class, 1);
    }

    public function test_payment_after_cancellation_never_reactivates_booking(): void
    {
        $booking = $this->booking(['status' => 'cancelled']);
        $payments = app(SandboxPaymentService::class);
        $payment = $payments->createPayment($booking);
        $payments->complete($payment);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertTrue($payment->fresh()->metadata['requires_review']);
        $this->assertNull($booking->fresh()->accessCode);
    }

    public function test_cancellation_returns_one_credit_at_twelve_hours_but_not_inside(): void
    {
        foreach ([12, 11] as $hours) {
            $booking = $this->booking(['status' => 'confirmed', 'payment_status' => 'paid', 'starts_at' => now()->addHours($hours)]);
            app(BookingCancellationService::class)->cancel($booking);
            app(BookingCancellationService::class)->cancel($booking);
            $this->assertSame($hours === 12 ? 1 : 0, $booking->user->fresh()->session_credits);
        }
    }

    public function test_confirmation_email_contains_booking_number(): void
    {
        $booking = $this->booking(['status' => 'confirmed', 'payment_status' => 'paid']);
        $mail = new BookingConfirmed($booking->load(['room', 'accessCode']));
        $this->assertStringContainsString('#'.$booking->id, $mail->render());
    }
}
