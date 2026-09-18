<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class AccessCode extends Model
{
    public const PENDING = 'pending';

    public const PENDING_MANUAL = 'pending_manual';

    public const PROVISIONED = 'provisioned';

    public const FAILED = 'failed';

    protected $fillable = [
        'ttlock_lock_id',
        'ttlock_passcode_id',
        'revoked_at',
        'access_notified_at',
        'booking_id',
        'code',
        'valid_from',
        'valid_until',
        'provision_status',
        'lock_response_log',
        'provisioned_at',
    ];

    protected function casts(): array
    {
        return [
            'ttlock_lock_id' => 'integer',
            'ttlock_passcode_id' => 'integer',
            'revoked_at' => 'datetime',
            'access_notified_at' => 'datetime',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'lock_response_log' => 'array',
            'provisioned_at' => 'datetime',
        ];
    }

    public function getReadyForUseAttribute(): bool
    {
        return ! $this->revoked_at && $this->provision_status === self::PROVISIONED
            && (config('lock.provider') !== 'ttlock' || $this->ttlock_passcode_id !== null);
    }

    protected static function booted(): void
    {
        static::updating(function (self $code) {
            if ($code->getOriginal('ttlock_lock_id') && $code->isDirty(['code', 'valid_from', 'valid_until', 'booking_id', 'ttlock_lock_id'])) {
                throw ValidationException::withMessages(['code' => 'Cancele a reserva e crie outra para alterar um PIN TTLock.']);
            }
        });
        static::deleting(function (self $code) {
            if ($code->ttlock_lock_id && ! $code->revoked_at && $code->valid_until->isFuture()) {
                throw ValidationException::withMessages(['code' => 'Cancele a reserva e aguarde a revogação antes de eliminar o PIN.']);
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
