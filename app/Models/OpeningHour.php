<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningHour extends Model
{
    protected $fillable = ['room_id', 'weekday', 'opens_at', 'closes_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
