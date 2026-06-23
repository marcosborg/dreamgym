<?php

namespace App\Services\Locks;

use App\Models\AccessCode;

class SimulatedLockProvider implements LockProvider
{
    public function provisionTemporaryPin(AccessCode $accessCode): AccessCode
    {
        $payload = [
            'driver' => 'simulated',
            'command' => 'set_temporary_pin',
            'code' => $accessCode->code,
            'valid_from' => $accessCode->valid_from->toIso8601String(),
            'valid_until' => $accessCode->valid_until->toIso8601String(),
            'sent_at' => now()->toIso8601String(),
            'result' => 'accepted',
            'note' => 'PIN unico por reserva; deve funcionar apenas na janela de validade.',
        ];

        $accessCode->update([
            'provision_status' => AccessCode::PROVISIONED,
            'lock_response_log' => $payload,
            'provisioned_at' => now(),
        ]);

        return $accessCode->fresh();
    }
}
