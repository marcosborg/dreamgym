<?php

namespace App\Console\Commands;

use App\Mail\BookingConfirmed;
use App\Models\AccessCode;
use App\Services\Locks\LockProvisioningService;
use App\Services\Locks\TtlockClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TtlockSync extends Command
{
    protected $signature = 'ttlock:sync';

    protected $description = 'Renovar tokens e reconciliar apenas acessos TTLock de reservas existentes';

    public function handle(LockProvisioningService $service, TtlockClient $client): int
    {
        if (config('lock.provider') !== 'ttlock') {
            $this->info('TTLock inativa.');

            return self::SUCCESS;
        }

        return Cache::lock('ttlock-sync', 1800)->get(function () use ($service, $client) {
            try {
                $client->refreshIfNeeded();
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
            // Cancelled access takes priority, including uncertain add/delete results.
            AccessCode::whereNotNull('ttlock_lock_id')->whereNull('revoked_at')
                ->whereHas('booking', fn ($q) => $q->where('status', 'cancelled'))
                ->each(fn ($code) => $service->revoke($code));

            AccessCode::where('valid_until', '>', now())->whereNull('revoked_at')
                ->whereHas('booking', fn ($q) => $q->where('status', 'confirmed')->where('payment_status', 'paid'))
                ->where(fn ($q) => $q->whereIn('provision_status', [AccessCode::PENDING, AccessCode::FAILED])->orWhere(function ($q) {
                    $q->whereNotNull('ttlock_passcode_id')->whereNull('access_notified_at');
                }))
                ->each(function ($code) use ($service) {
                    $code = $service->provision($code);
                    if ($code->ready_for_use && ! $code->access_notified_at) {
                        try {
                            $booking = $code->booking()->with(['room', 'accessCode'])->firstOrFail();
                            Mail::to($booking->customer_email)->send(new BookingConfirmed($booking));
                            $code->update(['access_notified_at' => now()]);
                        } catch (Throwable) {
                            $this->warn('Email de acesso pendente: reserva '.$code->booking_id);
                        }
                    }
                });
            $pending = AccessCode::where('provision_status', 'revoke_pending')->count();
            $this->info('Sincronização concluída. Revogações pendentes: '.$pending);

            return $pending ? self::FAILURE : self::SUCCESS;
        }) ?? self::SUCCESS;
    }
}
