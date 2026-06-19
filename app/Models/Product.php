<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public const TYPE_SINGLE_HOUR = 'single_hour';
    public const TYPE_SESSION_PACK = 'session_pack';
    public const TYPE_MEMBERSHIP = 'membership';
    public const TYPE_GROUP_HOUR = 'group_hour';

    protected $fillable = [
        'name',
        'type',
        'price_cents',
        'currency',
        'is_active',
        'sort_order',
        'credits',
        'days',
        'seats',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'credits' => 'integer',
            'days' => 'integer',
            'seats' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_cents / 100, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function isPurchaseProduct(): bool
    {
        return in_array($this->type, [self::TYPE_SESSION_PACK, self::TYPE_MEMBERSHIP], true);
    }
}
