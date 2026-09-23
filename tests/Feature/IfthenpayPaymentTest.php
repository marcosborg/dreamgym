<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payments\IfthenpayGatewayFactory;
use App\Services\Payments\IfthenpayPaymentService;
use App\Services\SiteSettings;
use Ifthenpay\PaymentGateway\Enums\Status;
use Ifthenpay\PaymentGateway\IfthenpayGateway;
use Ifthenpay\PaymentGateway\Service\MbwayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IfthenpayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'payments.provider' => 'ifthenpay',
            'payments.ifthenpay.backoffice_key' => '1234-1234-1234-1234',
            'payments.ifthenpay.mb_key' => 'ABC-123456',
            'payments.ifthenpay.mbway_key' => 'DEF-123456',
            'payments.ifthenpay.callback_secret' => 'testing-callback-secret',
            'payments.ifthenpay.env' => 'production',
        ]);
    }

    private function mockMbwayStatus(Status $status, string $transaction = 'transaction-123'): void
    {
        $method = \Mockery::mock(MbwayService::class);
        $method->shouldReceive('getPaymentStatus')->once()->with($transaction)->andReturn($status);
        $gateway = \Mockery::mock(IfthenpayGateway::class);
        $gateway->shouldReceive('mbway')->once()->andReturn($method);
        $this->mock(IfthenpayGatewayFactory::class)->shouldReceive('make')->once()->andReturn($gateway);
    }

    public function test_reconciliation_grants_credits_once_only_after_provider_confirms_payment(): void
    {
        $payment = $this->payment();
        $this->mockMbwayStatus(Status::PAID);
        $service = app(IfthenpayPaymentService::class);
        $service->reconcileMbway($payment);
        $service->reconcileMbway($payment);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(3, $payment->user->fresh()->session_credits);
        // A later authenticated callback remains idempotent.
        $this->app->forgetInstance(IfthenpayGatewayFactory::class);
        $this->get(route('ifthenpay.callback', $this->payload()))->assertOk();
        $this->assertSame(3, $payment->user->fresh()->session_credits);
    }

    public function test_pending_provider_status_does_not_grant_credits(): void
    {
        $payment = $this->payment();
        $this->mockMbwayStatus(Status::PENDING);
        app(IfthenpayPaymentService::class)->reconcileMbway($payment);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, $payment->user->fresh()->session_credits);
    }

    public function test_reconciliation_can_recover_a_paid_previous_attempt(): void
    {
        $payment = $this->payment();
        $metadata = $payment->metadata;
        $metadata['previous_attempts'] = [['payment_method' => 'mbway', 'ifthenpay' => $metadata['ifthenpay']]];
        $metadata['ifthenpay']['transactionId'] = 'new-request';
        $payment->update(['metadata' => $metadata]);
        $this->mockMbwayStatus(Status::PAID);
        app(IfthenpayPaymentService::class)->reconcileMbway($payment);
        $this->assertSame(3, $payment->user->fresh()->session_credits);
    }

    public function test_unavailable_old_attempt_does_not_block_a_paid_current_attempt(): void
    {
        $payment = $this->payment();
        $metadata = $payment->metadata;
        $metadata['previous_attempts'] = [['payment_method' => 'mbway', 'ifthenpay' => ['transactionId' => 'old-request']]];
        $payment->update(['metadata' => $metadata]);
        $method = \Mockery::mock(MbwayService::class);
        $method->shouldReceive('getPaymentStatus')->once()->with('old-request')->andThrow(new \RuntimeException('Unavailable'));
        $method->shouldReceive('getPaymentStatus')->once()->with('transaction-123')->andReturn(Status::PAID);
        $gateway = \Mockery::mock(IfthenpayGateway::class);
        $gateway->shouldReceive('mbway')->twice()->andReturn($method);
        $this->mock(IfthenpayGatewayFactory::class)->shouldReceive('make')->once()->andReturn($gateway);
        app(IfthenpayPaymentService::class)->reconcileMbway($payment);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(3, $payment->user->fresh()->session_credits);
    }

    public function test_reconciliation_does_not_query_multibanco_as_mbway(): void
    {
        $payment = $this->payment('multibanco');
        $this->mock(IfthenpayGatewayFactory::class)->shouldNotReceive('make');
        app(IfthenpayPaymentService::class)->reconcileMbway($payment);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_sync_command_confirms_a_paid_booking_and_sends_one_access_email(): void
    {
        Mail::fake();
        config(['lock.provider' => 'simulated']);
        $room = Room::create(['name' => 'Gym', 'capacity' => 1, 'slot_price_cents' => 1200, 'currency' => 'EUR']);
        $booking = Booking::create([
            'room_id' => $room->id, 'customer_name' => 'Test', 'customer_email' => 'test@example.com',
            'locale' => 'pt', 'booking_type' => 'single_hour', 'starts_at' => now()->addDay()->startOfHour(),
            'ends_at' => now()->addDay()->startOfHour()->addHour(), 'status' => 'pending',
            'payment_status' => 'pending', 'price_cents' => 1200, 'currency' => 'EUR',
        ]);
        $payment = $this->payment();
        $payment->update(['booking_id' => $booking->id, 'product_type' => 'single_hour']);
        $this->mockMbwayStatus(Status::PAID);
        $this->artisan('payments:sync')->assertSuccessful();
        $this->artisan('payments:sync')->assertSuccessful();
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(1, $booking->accessCode()->count());
        Mail::assertSent(BookingConfirmed::class, 1);
    }

    public function test_registration_uses_configured_authentication_without_logging_the_secret(): void
    {
        $method = \Mockery::mock(MbwayService::class);
        $method->shouldReceive('registerWebhook')->once()
            ->with(route('ifthenpay.callback'), ['apk' => 'testing-callback-secret'])
            ->andReturn('https://example.test/callback?apk=testing-callback-secret');
        $gateway = \Mockery::mock(IfthenpayGateway::class);
        $gateway->shouldReceive('mbway')->once()->andReturn($method);
        $this->mock(IfthenpayGatewayFactory::class)->shouldReceive('make')->once()->andReturn($gateway);
        $this->artisan('ifthenpay:register-webhooks', ['--method' => 'mbway'])
            ->expectsOutput('MB WAY webhook registered.')->assertSuccessful();
    }

    private function payment(string $method = 'mbway'): Payment
    {
        return Payment::create([
            'user_id' => User::factory()->create()->id,
            'provider' => 'ifthenpay', 'product_type' => 'session_pack',
            'reference' => 'DGTEST123', 'amount_cents' => 1200,
            'currency' => 'EUR', 'status' => 'pending',
            'metadata' => ['credits' => 3, 'payment_method' => $method, 'ifthenpay' => [
                'transactionId' => 'transaction-123', 'amount' => '12.00',
                'reference' => '123456789', 'entity' => '12345',
                'expireDate' => now()->addMinutes(4)->toDateTimeString(),
            ]],
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['oid' => 'DGTEST123', 'val' => '12.00',
            'apk' => 'testing-callback-secret', 'tid' => 'transaction-123', 'ref' => '123456789'], $overrides);
    }

    public function test_authenticated_callbacks_complete_both_methods_once_during_maintenance(): void
    {
        Setting::setValue(SiteSettings::MAINTENANCE_ENABLED, true);
        Setting::setValue(SiteSettings::MAINTENANCE_ALLOWED_IPS, []);
        foreach (['mbway', 'multibanco'] as $method) {
            $payment = $this->payment($method);
            $url = route('ifthenpay.callback', $this->payload());
            $this->get($url)->assertOk()->assertSee('OK');
            $this->get($url)->assertOk()->assertSee('OK');
            $this->assertSame('paid', $payment->fresh()->status);
            $this->assertSame(3, $payment->user->fresh()->session_credits);
            $payment->update(['reference' => 'DONE-'.$method]);
        }
        $this->get('/')->assertStatus(503);
    }

    public function test_wrong_secret_amount_or_transaction_never_grants_credits(): void
    {
        $payment = $this->payment();
        foreach ([['apk' => 'wrong'], ['val' => '0.01'], ['tid' => 'other']] as $override) {
            $this->get(route('ifthenpay.callback', $this->payload($override)))->assertStatus(422);
        }
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, $payment->user->session_credits);
    }

    public function test_delayed_callback_from_previous_mbway_attempt_is_accepted_once(): void
    {
        $payment = $this->payment();
        $metadata = $payment->metadata;
        $metadata['previous_attempts'] = [['payment_method' => 'mbway', 'ifthenpay' => $metadata['ifthenpay']]];
        $metadata['ifthenpay']['transactionId'] = 'new-request';
        $payment->update(['metadata' => $metadata]);
        $this->get(route('ifthenpay.callback', $this->payload()))->assertOk();
        $this->get(route('ifthenpay.callback', $this->payload(['tid' => 'new-request'])))->assertOk();
        $this->assertSame(3, $payment->user->fresh()->session_credits);
    }

    public function test_paid_callbacks_still_require_authentication(): void
    {
        $payment = $this->payment();
        $payment->update(['status' => 'paid']);
        $this->get(route('ifthenpay.callback', $this->payload(['apk' => 'wrong'])))->assertStatus(422);
    }

    public function test_existing_requests_and_paid_payments_are_never_reinitialized(): void
    {
        $this->mock(IfthenpayGatewayFactory::class)->shouldNotReceive('make');
        foreach (['mbway', 'multibanco'] as $method) {
            $payment = $this->payment($method);
            app(IfthenpayPaymentService::class)->initialize($payment, 'mbway', '900000000');
            $this->assertSame('transaction-123', $payment->fresh()->metadata['ifthenpay']['transactionId']);
            $payment->update(['status' => 'paid']);
            app(IfthenpayPaymentService::class)->initialize($payment, 'mbway', '900000000');
            $this->assertSame('paid', $payment->fresh()->status);
            $payment->update(['reference' => 'DONE-'.$method]);
        }
    }

    public function test_checkout_redirects_after_callback_and_hides_duplicate_payment_form(): void
    {
        $payment = $this->payment();
        $this->actingAs($payment->user)->get(route('purchase.checkout', $payment))
            ->assertOk()->assertDontSee('name="payment_method"', false);
        $payment->update(['status' => 'paid']);
        $this->get(route('purchase.checkout', $payment))->assertRedirect(route('purchase.confirmed', $payment));
    }
}
