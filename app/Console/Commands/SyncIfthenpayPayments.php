<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Payments\IfthenpayPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncIfthenpayPayments extends Command
{
    protected $signature = 'payments:sync {--payment= : Reconcile one existing payment ID}';

    protected $description = 'Reconcile pending MB WAY payments against the authenticated provider status';

    public function handle(IfthenpayPaymentService $service): int
    {
        if (config('payments.provider') !== 'ifthenpay') {
            $this->info('Ifthenpay inactive.');

            return self::SUCCESS;
        }

        return Cache::lock('ifthenpay-payments-sync', 3600)->get(function () use ($service) {
            $failed = 0;
            Payment::where('provider', 'ifthenpay')->where('status', 'pending')
                ->when($this->option('payment'), fn ($q) => $q->whereKey($this->option('payment')),
                    fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->orderBy('id')->each(function (Payment $payment) use ($service, &$failed) {
                    try {
                        $result = $service->reconcileMbway($payment);
                        if ($result->status === 'paid') {
                            $this->info('Payment '.$payment->id.' confirmed.');
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::warning('Ifthenpay reconciliation failed.', ['payment_id' => $payment->id, 'exception_type' => get_class($exception)]);
                        $this->warn('Payment '.$payment->id.' could not be verified.');
                    }
                });
            $this->info('Reconciliation finished; failures: '.$failed);

            return $failed ? self::FAILURE : self::SUCCESS;
        }) ?? self::SUCCESS;
    }
}
