<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'children_responsibility_accepted_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'bringing_children' => 'boolean',
            'price_cents' => 'integer',
            'seats_reserved' => 'integer',
        ];
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
