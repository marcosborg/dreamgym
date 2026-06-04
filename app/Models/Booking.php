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

    protected $fillable = [
        'room_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'locale',
        'starts_at',
        'ends_at',
        'status',
        'payment_status',
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
            'price_cents' => 'integer',
        ];
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
