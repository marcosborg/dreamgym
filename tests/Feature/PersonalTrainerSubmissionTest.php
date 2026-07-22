<?php

namespace Tests\Feature;

use App\Mail\PersonalTrainerDecisionMail;
use App\Mail\PersonalTrainerSubmittedMail;
use App\Models\PersonalTrainer;
use App\Models\PersonalTrainerSubmission;
use App\Models\User;
use App\Services\PersonalTrainerReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonalTrainerSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_save_an_incomplete_draft(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.personal-trainer.save'), [
            'name' => 'Ana Trainer',
            'show_email' => '0',
            'show_phone' => '0',
            'show_whatsapp' => '0',
            'publication_consent' => '0',
        ])->assertRedirect();

        $this->assertDatabaseHas('personal_trainer_submissions', [
            'user_id' => $user->id,
            'name' => 'Ana Trainer',
            'status' => PersonalTrainerSubmission::STATUS_DRAFT,
        ]);
    }

    public function test_submission_requires_complete_bilingual_content_and_consent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.personal-trainer.submit'), [
            'name' => 'Ana Trainer',
        ])->assertSessionHasErrors([
            'title_pt', 'title_en', 'specialties_pt', 'specialties_en', 'bio_pt', 'bio_en', 'publication_consent',
        ]);

        $this->assertDatabaseCount('personal_trainer_submissions', 0);
    }

    public function test_customer_can_submit_with_photo_and_admins_are_notified(): void
    {
        Storage::fake('public');
        Mail::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.personal-trainer.submit'), [
            ...$this->validPayload(),
            'photo' => UploadedFile::fake()->image('ana.jpg', 400, 400),
        ])->assertRedirect(route('account.personal-trainer.edit'));

        $submission = PersonalTrainerSubmission::firstOrFail();
        $this->assertSame(PersonalTrainerSubmission::STATUS_PENDING, $submission->status);
        Storage::disk('public')->assertExists($submission->photo_path);
        Mail::assertSent(PersonalTrainerSubmittedMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_uploaded_photo_is_served_without_the_public_storage_link(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('ana.jpg', 400, 400);
        $path = $photo->store('personal-trainers/submissions', 'public');
        $submission = PersonalTrainerSubmission::create([
            'user_id' => User::factory()->create()->id,
            'status' => PersonalTrainerSubmission::STATUS_DRAFT,
            ...$this->validPayload(),
            'photo_path' => $path,
        ]);

        $this->assertStringStartsWith('/media/personal-trainers/', $submission->photo_url);
        $this->get($submission->photo_url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_pending_profile_is_not_public_until_approved(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $submission = PersonalTrainerSubmission::create([
            'user_id' => $user->id,
            'status' => PersonalTrainerSubmission::STATUS_PENDING,
            ...$this->validPayload(),
            'submitted_at' => now(),
        ]);

        $this->get(route('home'))->assertDontSee('Ana Trainer');

        $trainer = app(PersonalTrainerReviewService::class)->approve($submission, $admin);

        $this->assertTrue($trainer->is_active);
        $this->assertSame($user->id, $trainer->user_id);
        $this->get(route('home'))->assertSee('Ana Trainer')->assertSee('Treinadora de força');
        Mail::assertSent(PersonalTrainerDecisionMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_pending_update_preserves_the_published_profile(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $trainer = PersonalTrainer::create([
            'user_id' => $user->id,
            'name' => 'Nome publicado',
            'bio_pt' => 'Bio publicada',
            'bio_en' => 'Published bio',
            'is_active' => true,
        ]);
        $submission = PersonalTrainerSubmission::create([
            'user_id' => $user->id,
            'personal_trainer_id' => $trainer->id,
            'status' => PersonalTrainerSubmission::STATUS_PENDING,
            ...$this->validPayload(['name' => 'Nome novo']),
            'submitted_at' => now(),
        ]);

        $this->get(route('home'))->assertSee('Nome publicado')->assertDontSee('Nome novo');
        app(PersonalTrainerReviewService::class)->requestChanges($submission, $admin, 'Revê a descrição.');

        $this->assertDatabaseHas('personal_trainers', ['id' => $trainer->id, 'name' => 'Nome publicado']);
        $this->assertDatabaseHas('personal_trainer_submissions', [
            'id' => $submission->id,
            'status' => PersonalTrainerSubmission::STATUS_CHANGES_REQUESTED,
            'review_note' => 'Revê a descrição.',
        ]);
    }

    public function test_submission_pages_are_private_and_scoped_to_the_current_user(): void
    {
        $this->get(route('account.personal-trainer.edit'))->assertRedirect(route('login'));

        $owner = User::factory()->create();
        $other = User::factory()->create();
        PersonalTrainerSubmission::create([
            'user_id' => $owner->id,
            'status' => PersonalTrainerSubmission::STATUS_DRAFT,
            'name' => 'Perfil privado',
        ]);

        $this->actingAs($other)->get(route('account.personal-trainer.edit'))
            ->assertOk()
            ->assertDontSee('Perfil privado');
    }

    public function test_admin_can_open_submission_list_and_review_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $submission = PersonalTrainerSubmission::create([
            'user_id' => $owner->id,
            'status' => PersonalTrainerSubmission::STATUS_PENDING,
            ...$this->validPayload(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/personal-trainer-submissions')
            ->assertOk()
            ->assertSee('Ana Trainer');

        $this->actingAs($admin)
            ->get("/admin/personal-trainer-submissions/{$submission->id}/edit")
            ->assertOk()
            ->assertSee('Aprovar e publicar')
            ->assertSee('Pedir alterações');
    }

    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Ana Trainer',
            'title_pt' => 'Treinadora de força',
            'title_en' => 'Strength coach',
            'specialties_pt' => 'Força, mobilidade',
            'specialties_en' => 'Strength, mobility',
            'bio_pt' => 'Descrição profissional em português.',
            'bio_en' => 'Professional biography in English.',
            'email' => 'ana@example.test',
            'phone' => '+351 910 000 000',
            'show_email' => true,
            'show_phone' => true,
            'show_whatsapp' => true,
            'publication_consent' => true,
            ...$overrides,
        ];
    }
}
