<?php

namespace App\Services\Locks;

use App\Models\AccessCode;

class ManualIhrLockProvider implements LockProvider
{
    public function provisionTemporaryPin(AccessCode $accessCode): AccessCode
    {
        $booking = $accessCode->booking;

        $payload = [
            'driver' => 'manual_ihr',
            'command' => 'manual_set_temporary_pin',
            'code' => $accessCode->code,
            'valid_from' => $accessCode->valid_from->toIso8601String(),
            'valid_until' => $accessCode->valid_until->toIso8601String(),
            'generated_at' => now()->toIso8601String(),
            'booking_id' => $accessCode->booking_id,
            'customer_name' => $booking?->customer_name,
            'instructions' => [
                'Programar este PIN na app/hub iHR Smart ou diretamente na fechadura L153.',
                'Configurar validade apenas entre valid_from e valid_until.',
                'Cada reserva tem um PIN unico; nao reutilizar como codigo fixo de cliente.',
                'Depois de programar, usar a acao admin "Marcar como configurado manualmente".',
            ],
        ];

        $accessCode->update([
            'provision_status' => AccessCode::PENDING_MANUAL,
            'lock_response_log' => $payload,
            'provisioned_at' => null,
        ]);

        return $accessCode->fresh();
    }
}
