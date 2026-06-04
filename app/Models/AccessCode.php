<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCode extends Model
{
    protected $fillable = [
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
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'lock_response_log' => 'array',
            'provisioned_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
