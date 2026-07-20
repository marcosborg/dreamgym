<?php

namespace App\Services;

use App\Mail\PersonalTrainerDecisionMail;
use App\Models\PersonalTrainer;
use App\Models\PersonalTrainerSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class PersonalTrainerReviewService
{
    public function approve(PersonalTrainerSubmission $submission, User $reviewer): PersonalTrainer
    {
        if ($submission->status !== PersonalTrainerSubmission::STATUS_PENDING) {
            throw new InvalidArgumentException('Apenas candidaturas pendentes podem ser aprovadas.');
        }

        $trainer = DB::transaction(function () use ($submission, $reviewer) {
            $trainer = $submission->personalTrainer ?: $submission->user->personalTrainer ?: new PersonalTrainer;

            if ($trainer->exists && $trainer->user_id && $trainer->user_id !== $submission->user_id) {
                throw new InvalidArgumentException('O perfil selecionado já pertence a outra conta.');
            }
            $trainer->fill([
                'user_id' => $submission->user_id,
                'name' => $submission->name,
                'title_pt' => $submission->title_pt,
                'title_en' => $submission->title_en,
                'specialties_pt' => $submission->specialties_pt,
                'specialties_en' => $submission->specialties_en,
                'bio' => $submission->bio_pt,
                'bio_pt' => $submission->bio_pt,
                'bio_en' => $submission->bio_en,
                'email' => $submission->email,
                'phone' => $submission->phone,
                'photo_path' => $submission->photo_path,
                'show_email' => $submission->show_email,
                'show_phone' => $submission->show_phone,
                'show_whatsapp' => $submission->show_whatsapp,
                'is_active' => true,
            ]);
            $trainer->save();

            $submission->update([
                'personal_trainer_id' => $trainer->id,
                'status' => PersonalTrainerSubmission::STATUS_APPROVED,
                'review_note' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $trainer;
        });

        Mail::to($submission->user)->send(new PersonalTrainerDecisionMail($submission->fresh(), 'approved'));

        return $trainer;
    }

    public function requestChanges(PersonalTrainerSubmission $submission, User $reviewer, string $note): void
    {
        $this->decide($submission, $reviewer, PersonalTrainerSubmission::STATUS_CHANGES_REQUESTED, $note);
    }

    public function reject(PersonalTrainerSubmission $submission, User $reviewer, string $note): void
    {
        $this->decide($submission, $reviewer, PersonalTrainerSubmission::STATUS_REJECTED, $note);
    }

    private function decide(PersonalTrainerSubmission $submission, User $reviewer, string $status, string $note): void
    {
        if ($submission->status !== PersonalTrainerSubmission::STATUS_PENDING) {
            throw new InvalidArgumentException('Apenas candidaturas pendentes podem ser revistas.');
        }

        $submission->update([
            'status' => $status,
            'review_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Mail::to($submission->user)->send(new PersonalTrainerDecisionMail($submission->fresh(), $status));
    }
}
