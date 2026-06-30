<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\SandboxPaymentService;
use Ifthenpay\PaymentGateway\Enums\Status;
use Ifthenpay\PaymentGateway\Model\Mbway;
use Ifthenpay\PaymentGateway\Model\MultibancoDynamic;
use Ifthenpay\PaymentGateway\RequestObj\WebhookRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class IfthenpayPaymentService
{
    public function __construct(
        private readonly IfthenpayGatewayFactory $gatewayFactory,
        private readonly SandboxPaymentService $completion,
    ) {}

    public function createPayment(Booking $booking): Payment
    {
        return Payment::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'provider' => 'ifthenpay',
                'reference' => $this->orderId($booking),
                'product_type' => $booking->booking_type,
                'amount_cents' => $booking->price_cents,
                'currency' => $booking->currency,
                'status' => 'pending',
                'terms_accepted_at' => $booking->terms_accepted_at,
                'metadata' => [
                    'provider_env' => config('payments.ifthenpay.env'),
                    'label' => 'Ifthenpay',
                ],
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $product
     */
    public function createPurchasePayment(User $user, array $product, Room $room): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'product_type' => $product['type'],
            'provider' => 'ifthenpay',
            'reference' => 'DGP'.$user->id.Str::upper(Str::random(5)),
            'amount_cents' => $product['price_cents'],
            'currency' => $product['currency'] ?? $room->currency,
            'status' => 'pending',
            'metadata' => [
                'provider_env' => config('payments.ifthenpay.env'),
                'label' => $product['name'],
                'product_id' => $product['id'],
                'credits' => $product['credits'] ?? null,
                'days' => $product['days'] ?? null,
            ],
        ]);
    }

    public function initialize(Payment $payment, string $method, ?string $mobileNumber = null): Payment
    {
        $gateway = $this->gatewayFactory->make();
        $amount = $this->amount($payment);
        $description = Str::limit($this->description($payment), 100, '');

        if ($method === 'mbway') {
            $result = $gateway->mbway()->initPayment(
                $payment->reference,
                $amount,
                $this->normalizeMobileNumber($mobileNumber),
                $description,
                $payment->booking?->customer_email ?? $payment->user?->email,
            );
        } else {
            $method = 'multibanco';
            $result = $gateway->multibancoDynamic()->initPayment(
                $payment->reference,
                $amount,
                $description,
            );
        }

        $metadata = array_merge($payment->metadata ?? [], [
            'provider_env' => config('payments.ifthenpay.env'),
            'payment_method' => $method,
            'ifthenpay' => $result->toArray(),
        ]);

        if (method_exists($result, 'getEntity')) {
            $metadata['ifthenpay']['entity'] = $result->getEntity();
        }

        $payment->update([
            'provider' => 'ifthenpay',
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        return $payment->fresh(['booking', 'user']);
    }

    public function handleCallback(array $payload): ?Payment
    {
        $payment = Payment::query()
            ->where('reference', $payload['oid'] ?? null)
            ->where('provider', 'ifthenpay')
            ->first();

        if (! $payment || $payment->status === 'paid') {
            return $payment;
        }

        $method = $payment->metadata['payment_method'] ?? $this->methodFromPayload($payload);
        $webhook = new WebhookRequest(
            (string) ($payload['val'] ?? ''),
            (string) ($payload['oid'] ?? ''),
            (string) ($payload['apk'] ?? ''),
            $payload['tid'] ?? null,
            $payload['ref'] ?? null,
        );

        $gateway = $this->gatewayFactory->make();

        if ($method === 'mbway') {
            $gateway->mbway()->validateWebhook($webhook, $this->mbwayModel($payment));
        } else {
            $gateway->multibancoDynamic()->validateWebhook($webhook, $this->multibancoModel($payment));
        }

        return DB::transaction(function () use ($payment) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status === 'paid') {
                return $locked->fresh(['booking', 'user']);
            }

            if ($locked->booking_id) {
                $this->completion->complete($locked);
            } else {
                $this->completion->completePurchase($locked);
            }

            return $locked->fresh(['booking', 'user']);
        });
    }

    public function markInitializationFailure(Payment $payment, Throwable $exception): Payment
    {
        $metadata = array_merge($payment->metadata ?? [], [
            'payment_method_error' => [
                'provider' => 'ifthenpay',
                'message' => $exception->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ],
        ]);

        $payment->update(['metadata' => $metadata]);

        return $payment->fresh();
    }

    private function orderId(Booking $booking): string
    {
        return 'DG'.$booking->id.Str::upper(Str::random(5));
    }

    private function amount(Payment $payment): string
    {
        return number_format($payment->amount_cents / 100, 2, '.', '');
    }

    private function description(Payment $payment): string
    {
        if ($payment->booking) {
            return 'Dream Gym reserva '.$payment->booking->starts_at->format('d/m/Y H:i');
        }

        return 'Dream Gym '.($payment->metadata['label'] ?? $payment->product_type);
    }

    private function normalizeMobileNumber(?string $mobileNumber): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobileNumber);

        if (str_starts_with($digits, '351') && strlen($digits) === 12) {
            return substr($digits, 3);
        }

        return $digits;
    }

    private function methodFromPayload(array $payload): string
    {
        return strtoupper((string) ($payload['pm'] ?? '')) === 'MBWAY' ? 'mbway' : 'multibanco';
    }

    private function mbwayModel(Payment $payment): Mbway
    {
        $data = $payment->metadata['ifthenpay'] ?? [];

        return new Mbway(
            (string) ($data['amount'] ?? $this->amount($payment)),
            $payment->reference,
            (string) ($data['transactionId'] ?? ''),
            (string) ($data['mobileNumber'] ?? ''),
            Status::tryFrom($data['status'] ?? 'pending') ?? Status::PENDING,
        );
    }

    private function multibancoModel(Payment $payment): MultibancoDynamic
    {
        $data = $payment->metadata['ifthenpay'] ?? [];

        return new MultibancoDynamic(
            (string) ($data['amount'] ?? $this->amount($payment)),
            $payment->reference,
            (string) ($data['entity'] ?? ''),
            (string) ($data['reference'] ?? ''),
            (string) ($data['transactionId'] ?? ''),
            Status::tryFrom($data['status'] ?? 'pending') ?? Status::PENDING,
        );
    }
}
