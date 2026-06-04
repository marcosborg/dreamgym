<?php

namespace App\Services;

use App\Models\AccessCode;

class SimulatedLockService
{
    public function provision(AccessCode $accessCode): AccessCode
    {
        $payload = [
            'driver' => 'simulated',
            'command' => 'set_temporary_pin',
            'code' => $accessCode->code,
            'valid_from' => $accessCode->valid_from->toIso8601String(),
            'valid_until' => $accessCode->valid_until->toIso8601String(),
            'sent_at' => now()->toIso8601String(),
            'result' => 'accepted',
        ];

        $accessCode->update([
            'provision_status' => 'provisioned',
            'lock_response_log' => $payload,
            'provisioned_at' => now(),
        ]);

        return $accessCode->fresh();
    }
}
