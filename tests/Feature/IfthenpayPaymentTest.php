<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payments\IfthenpayGatewayFactory;
use App\Services\Payments\IfthenpayPaymentService;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
