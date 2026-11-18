<?php

namespace App\Console\Commands;

use App\Models\AccessCode;
use App\Services\BookingMail;
use App\Services\Locks\LockProvisioningService;
use App\Services\Locks\TtlockClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TtlockSync extends Command
{
    protected $signature = 'ttlock:sync {--allow-pending : Permitir a publicação de código com pendências físicas reportadas no ADMIN}';

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
                ->orderBy('valid_until')->get()->each(function ($code) use ($service) {
                    $code = $service->provision($code);
                    if (! $code->ready_for_use) {
                        $this->warn('Acesso pendente: reserva '.$code->booking_id.' — '.($code->lock_response_log['error'] ?? 'Aguarda confirmação TTLock'));
                    }
                    if ($code->ready_for_use && ! $code->access_notified_at) {
                        try {
                            $booking = $code->booking()->with(['room', 'accessCode'])->firstOrFail();
                            BookingMail::confirmed($booking);
                            $code->update(['access_notified_at' => now()]);
                        } catch (Throwable) {
                            $this->warn('Email de acesso pendente: reserva '.$code->booking_id);
                        }
                    }
                });
            $pending = AccessCode::where('provision_status', 'revoke_pending')->count();
            $unresolved = AccessCode::where('valid_until', '>', now())->whereNull('revoked_at')
                ->whereHas('booking', fn ($q) => $q->where('status', 'confirmed')->where('payment_status', 'paid'))
                ->where(fn ($q) => $q->where('provision_status', '!=', AccessCode::PROVISIONED)
                    ->orWhereNull('ttlock_passcode_id')->orWhereNull('access_notified_at'))->count();
            $this->info(now()->toIso8601String().' — Acessos/emails pendentes: '.$unresolved.'. Revogações pendentes: '.$pending);
            Cache::put('ttlock-last-sync', now()->toIso8601String(), now()->addDay());
            if ($pending || $unresolved) {
                Log::warning('TTLock: sincronização com pendências.', ['access_pending' => $unresolved, 'revoke_pending' => $pending]);
            }

            return ($pending || $unresolved) && ! $this->option('allow-pending') ? self::FAILURE : self::SUCCESS;
        }) ?? self::SUCCESS;
    }
}
