<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'ttlock_lock_id',
        'name',
        'description',
        'name_pt',
        'description_pt',
        'capacity',
        'slot_price_cents',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'slot_price_cents' => 'integer',
        ];
    }

    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'pt' ? ($this->name_pt ?: $this->name) : $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'pt' ? ($this->description_pt ?: $this->description) : $this->description;
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function blackoutPeriods(): HasMany
    {
        return $this->hasMany(BlackoutPeriod::class);
    }
}
