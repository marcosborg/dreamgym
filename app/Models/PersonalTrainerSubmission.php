<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalTrainerSubmission extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'personal_trainer_id', 'status', 'name', 'title_pt', 'title_en',
        'specialties_pt', 'specialties_en', 'bio_pt', 'bio_en', 'email', 'phone',
        'photo_path', 'show_email', 'show_phone', 'show_whatsapp', 'publication_consent',
        'review_note', 'reviewed_by', 'submitted_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_whatsapp' => 'boolean',
            'publication_consent' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalTrainer(): BelongsTo
    {
        return $this->belongsTo(PersonalTrainer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CHANGES_REQUESTED, self::STATUS_REJECTED], true);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? route('personal-trainer.photo', ['path' => $this->photo_path], false)
            : null;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_CHANGES_REQUESTED => 'Alterações solicitadas',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_REJECTED => 'Rejeitado',
        ];
    }

    public function statusLabel(): string
    {
        $key = 'site.pt_status_'.$this->status;

        return __($key) === $key ? (self::statusOptions()[$this->status] ?? $this->status) : __($key);
    }
}
