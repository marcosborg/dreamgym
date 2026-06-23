<?php

namespace App\Services\Locks;

use App\Models\AccessCode;
use Throwable;

class LockProvisioningService
{
    public function __construct(private readonly LockProvider $provider) {}

    public function provision(AccessCode $accessCode): AccessCode
    {
        try {
            return $this->provider->provisionTemporaryPin($accessCode);
        } catch (Throwable $exception) {
            $accessCode->update([
                'provision_status' => AccessCode::FAILED,
                'lock_response_log' => [
                    'driver' => config('lock.provider'),
                    'command' => 'set_temporary_pin',
                    'code' => $accessCode->code,
                    'valid_from' => $accessCode->valid_from->toIso8601String(),
                    'valid_until' => $accessCode->valid_until->toIso8601String(),
                    'failed_at' => now()->toIso8601String(),
                    'error' => $exception->getMessage(),
                    'admin_note' => 'Reserva confirmada. Corrigir a configuracao da fechadura e tentar provisionar novamente.',
                ],
                'provisioned_at' => null,
            ]);

            return $accessCode->fresh();
        }
    }

    public function markManuallyConfigured(AccessCode $accessCode): AccessCode
    {
        $log = $accessCode->lock_response_log ?? [];
        $log['manual_configured_at'] = now()->toIso8601String();
        $log['manual_configured_note'] = 'PIN marcado pelo admin como configurado na iHR Smart/L153.';

        $accessCode->update([
            'provision_status' => AccessCode::PROVISIONED,
            'lock_response_log' => $log,
            'provisioned_at' => now(),
        ]);

        return $accessCode->fresh();
    }
}
