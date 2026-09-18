<?php

namespace App\Services\Locks;

use App\Models\AccessCode;
use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TtlockLockProvider implements LockProvider
{
    public function __construct(private readonly TtlockClient $client) {}

    public function provisionTemporaryPin(AccessCode $accessCode): AccessCode
    {
        return Cache::lock('ttlock-pin-'.$accessCode->id, 180)->block(10, function () use ($accessCode) {
            $accessCode = $accessCode->fresh(['booking.room']);
            $booking = $accessCode->booking;
            if ($booking->status !== Booking::STATUS_CONFIRMED || $booking->payment_status !== 'paid'
                || $accessCode->revoked_at || $accessCode->valid_until->isPast()) {
                throw new RuntimeException('A reserva não permite ativar este código.');
            }
            if (! preg_match('/^[1-6]{4,9}$/', $accessCode->code)) {
                throw new RuntimeException('O PIN tem de conter 4 a 9 dígitos de 1 a 6.');
            }
            $lockId = (int) $booking->room->ttlock_lock_id;
            if (! $lockId || ($accessCode->ttlock_lock_id && $accessCode->ttlock_lock_id !== $lockId)) {
                throw new RuntimeException('A sala não tem uma associação TTLock válida.');
            }
            if ($accessCode->ttlock_passcode_id && $accessCode->provision_status === AccessCode::PROVISIONED) {
                return $accessCode;
            }
            $accessCode->update(['ttlock_lock_id' => $lockId]);
            $name = 'dreamgym-reservation-'.$booking->id.'-access-'.$accessCode->id;
            $existing = $this->findPasscode($lockId, $name, $accessCode);
            if ($existing) {
                return $this->markProvisioned($accessCode, $existing);
            }
            $detail = $this->client->request('/v3/lock/detail', ['lockId' => $lockId]);
            if ((int) ($detail['keyboardPwdVersion'] ?? 0) !== 4) {
                throw new RuntimeException('A fechadura não suporta PINs personalizados V4.');
            }
            $response = $this->client->request('/v3/keyboardPwd/add', [
                'lockId' => $lockId,
                'keyboardPwd' => $accessCode->code,
                'keyboardPwdName' => $name,
                'startDate' => $accessCode->valid_from->getTimestampMs(),
                'endDate' => $accessCode->valid_until->getTimestampMs(),
                'addType' => 2,
            ]);
            if (empty($response['keyboardPwdId'])) {
                throw new RuntimeException('A TTLock não confirmou a criação do PIN.');
            }

            return $this->markProvisioned($accessCode, (int) $response['keyboardPwdId']);
        });
    }

    public function revoke(AccessCode $accessCode): AccessCode
    {
        return Cache::lock('ttlock-pin-'.$accessCode->id, 180)->block(10, function () use ($accessCode) {
            $accessCode = $accessCode->fresh(['booking']);
            if ($accessCode->booking->status !== Booking::STATUS_CANCELLED) {
                throw new RuntimeException('Só podem ser revogados códigos de reservas canceladas.');
            }
            if ($accessCode->revoked_at) {
                return $accessCode;
            }
            $lockId = (int) $accessCode->ttlock_lock_id;
            if (! $lockId || $accessCode->valid_until->isPast()) {
                $accessCode->update(['provision_status' => 'revoked', 'revoked_at' => now()]);

                return $accessCode->fresh();
            }
            $name = 'dreamgym-reservation-'.$accessCode->booking_id.'-access-'.$accessCode->id;
            $pinId = $this->findPasscode($lockId, $name, $accessCode);
            if ($pinId) {
                $this->client->request('/v3/keyboardPwd/delete', ['lockId' => $lockId, 'keyboardPwdId' => $pinId, 'deleteType' => 2]);
            }
            $accessCode->update(['provision_status' => 'revoked', 'revoked_at' => now()]);

            return $accessCode->fresh();
        });
    }

    private function findPasscode(int $lockId, string $name, AccessCode $accessCode): ?int
    {
        for ($page = 1; $page <= 5; $page++) {
            $data = $this->client->request('/v3/lock/listKeyboardPwd', ['lockId' => $lockId, 'pageNo' => $page, 'pageSize' => 100]);
            if (! is_array($data['list'] ?? null)) {
                throw new RuntimeException('Não foi possível reconciliar os PINs existentes.');
            }
            foreach ($data['list'] as $pin) {
                if (($pin['keyboardPwdName'] ?? '') === $name) {
                    if ((string) ($pin['keyboardPwd'] ?? '') !== $accessCode->code
                        || (int) ($pin['startDate'] ?? 0) !== $accessCode->valid_from->getTimestampMs()
                        || (int) ($pin['endDate'] ?? 0) !== $accessCode->valid_until->getTimestampMs()
                        || empty($pin['keyboardPwdId']) || in_array((int) ($pin['status'] ?? 1), [2, 3, 4, 5, 6, 7, 8, 9], true)) {
                        throw new RuntimeException('Existe um PIN TTLock divergente. É necessária revisão manual.');
                    }

                    return (int) $pin['keyboardPwdId'];
                }
            }
            if (count($data['list']) < 100) {
                return null;
            }
        }
        throw new RuntimeException('A lista TTLock está incompleta.');
    }

    private function markProvisioned(AccessCode $accessCode, int $pinId): AccessCode
    {
        $accessCode->update([
            'ttlock_passcode_id' => $pinId,
            'provision_status' => AccessCode::PROVISIONED,
            'provisioned_at' => now(),
            'lock_response_log' => ['driver' => 'ttlock', 'result' => 'accepted', 'lock_id' => $accessCode->ttlock_lock_id, 'passcode_id' => $pinId],
        ]);

        return $accessCode->fresh();
    }
}
