<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalTrainer extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'title_pt',
        'title_en',
        'specialties_pt',
        'specialties_en',
        'email',
        'phone',
        'bio',
        'bio_pt',
        'bio_en',
        'photo_path',
        'show_email',
        'show_phone',
        'show_whatsapp',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_whatsapp' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(PersonalTrainerSubmission::class);
    }

    public function localized(string $field): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'pt';

        return $this->{$field.'_'.$locale} ?: $this->{$field.'_pt'} ?: ($field === 'bio' ? $this->bio : null);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? route('personal-trainer.photo', ['path' => $this->photo_path], false)
            : null;
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)) ?: [])
            ->filter()->take(2)->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->phone);

        return $number ? 'https://wa.me/'.$number : null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
