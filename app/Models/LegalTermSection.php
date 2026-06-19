<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LegalTermSection extends Model
{
    protected $fillable = [
        'title_pt',
        'body_pt',
        'title_en',
        'body_en',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function localizedTitle(?string $locale = null): string
    {
        return $this->{'title_' . $this->normalizedLocale($locale)};
    }

    public function localizedBody(?string $locale = null): string
    {
        return $this->{'body_' . $this->normalizedLocale($locale)};
    }

    private function normalizedLocale(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' ? 'en' : 'pt';
    }
}
