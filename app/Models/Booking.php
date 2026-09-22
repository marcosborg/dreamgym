<?php

namespace App\Models;

use App\Services\Locks\LockProvisioningService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Booking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_SINGLE_HOUR = 'single_hour';

    public const TYPE_GROUP_HOUR = 'group_hour';

    public const PAID_WITH_PAYMENT = 'payment';

    public const PAID_WITH_CREDITS = 'credits';

    public const PAID_WITH_MEMBERSHIP = 'membership';

    protected $fillable = [
        'room_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'locale',
        'bringing_children',
        'children_responsibility_accepted_at',
        'terms_accepted_at',
        'booking_type',
        'seats_reserved',
        'starts_at',
        'ends_at',
        'status',
        'payment_status',
        'paid_with',
        'price_cents',
        'currency',
        'payment_reference',
        'confirmed_at',
        'payment_expires_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'children_responsibility_accepted_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'bringing_children' => 'boolean',
            'price_cents' => 'integer',
            'seats_reserved' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $booking) {
            if ($booking->isDirty(['room_id', 'starts_at', 'ends_at', 'payment_status', 'customer_email']) && $booking->accessCode?->ttlock_lock_id) {
                throw ValidationException::withMessages(['status' => 'Cancele esta reserva e crie outra para alterar um acesso TTLock.']);
            }
            if ($booking->isDirty('status') && $booking->accessCode?->ttlock_lock_id
                && $booking->status !== self::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['status' => 'Não é possível reativar ou repor uma reserva TTLock como pendente.']);
            }
        });
        static::updated(function (self $booking) {
            if ($booking->wasChanged('status') && $booking->status === self::STATUS_CANCELLED) {
                DB::afterCommit(function () use ($booking) {
                    if ($code = $booking->accessCode()->first()) {
                        app(LockProvisioningService::class)->revoke($code);
                    }
                });
            }
        });
        static::deleting(function (self $booking) {
            $code = $booking->accessCode;
            if ($code?->ttlock_lock_id && ! $code->revoked_at && $code->valid_until->isFuture()) {
                throw ValidationException::withMessages(['status' => 'Cancele e revogue o acesso antes de eliminar a reserva.']);
            }
        });
    }

    public function paymentDeadline(): Carbon
    {
        return ($this->payment_expires_at ?? ($this->created_at ?? now())->copy()->addMinutes(15))->min($this->starts_at);
    }

    public function paymentHoldExpired(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->paymentDeadline()->lessThanOrEqualTo(now());
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->user_id !== null
            && $this->status !== self::STATUS_CANCELLED
            && $this->starts_at->isFuture();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function accessCode(): HasOne
    {
        return $this->hasOne(AccessCode::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_cents / 100, 2, ',', ' ').' '.$this->currency;
    }
}
