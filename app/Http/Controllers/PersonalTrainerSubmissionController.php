<?php

namespace App\Http\Controllers;

use App\Mail\PersonalTrainerSubmittedMail;
use App\Models\PersonalTrainerSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonalTrainerSubmissionController extends Controller
{
    public function edit(Request $request): View
    {
        $submission = $this->editableOrLatest($request);

        return view('account.personal-trainer', compact('submission'));
    }

    public function save(Request $request): RedirectResponse
    {
        $submission = $this->editableOrLatest($request);
        abort_if($submission && ! $submission->isEditable(), 422, __('site.pt_submission_locked'));

        $data = $this->validateSubmission($request, false);
        $submission = $submission ?: new PersonalTrainerSubmission([
            'user_id' => $request->user()->id,
            'status' => PersonalTrainerSubmission::STATUS_DRAFT,
        ]);

        $data['photo_path'] = $this->storePhoto($request, $submission);
        $submission->fill($data);
        $submission->status = PersonalTrainerSubmission::STATUS_DRAFT;
        $submission->save();

        return back()->with('status', __('site.pt_draft_saved'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $submission = $this->editableOrLatest($request);
        abort_if($submission && ! $submission->isEditable(), 422, __('site.pt_submission_locked'));

        $data = $this->validateSubmission($request, true);
        $submission = $submission ?: new PersonalTrainerSubmission(['user_id' => $request->user()->id]);
        $data['photo_path'] = $this->storePhoto($request, $submission);
        $submission->fill($data);
        $submission->fill([
            'status' => PersonalTrainerSubmission::STATUS_PENDING,
            'personal_trainer_id' => $request->user()->personalTrainer?->id,
            'review_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'submitted_at' => now(),
        ]);
        $submission->save();

        User::query()->where('is_admin', true)->each(
            fn (User $admin) => Mail::to($admin)->send(new PersonalTrainerSubmittedMail($submission))
        );

        return redirect()->route('account.personal-trainer.edit')->with('status', __('site.pt_submitted'));
    }

    private function editableOrLatest(Request $request): ?PersonalTrainerSubmission
    {
        $latest = $request->user()->personalTrainerSubmissions()->latest()->first();

        if ($latest?->status === PersonalTrainerSubmission::STATUS_APPROVED) {
            return new PersonalTrainerSubmission([
                'user_id' => $request->user()->id,
                'personal_trainer_id' => $latest->personal_trainer_id,
                'status' => PersonalTrainerSubmission::STATUS_DRAFT,
                ...$latest->only([
                    'name', 'title_pt', 'title_en', 'specialties_pt', 'specialties_en',
                    'bio_pt', 'bio_en', 'email', 'phone', 'photo_path', 'show_email',
                    'show_phone', 'show_whatsapp', 'publication_consent',
                ]),
            ]);
        }

        return $latest;
    }

    private function validateSubmission(Request $request, bool $submitting): array
    {
        $required = $submitting ? ['required'] : ['nullable'];

        $data = $request->validate([
            'name' => [...$required, 'string', 'max:120'],
            'title_pt' => [...$required, 'string', 'max:160'],
            'title_en' => [...$required, 'string', 'max:160'],
            'specialties_pt' => [...$required, 'string', 'max:1000'],
            'specialties_en' => [...$required, 'string', 'max:1000'],
            'bio_pt' => [...$required, 'string', 'max:3000'],
            'bio_en' => [...$required, 'string', 'max:3000'],
            'email' => ['nullable', 'email', 'max:160', Rule::requiredIf($submitting && $request->boolean('show_email'))],
            'phone' => ['nullable', 'string', 'max:40', Rule::requiredIf($submitting && ($request->boolean('show_phone') || $request->boolean('show_whatsapp')))],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=200,min_height=200'],
            'show_email' => ['nullable', 'boolean'],
            'show_phone' => ['nullable', 'boolean'],
            'show_whatsapp' => ['nullable', 'boolean'],
            'publication_consent' => [$submitting ? 'accepted' : 'nullable', 'boolean'],
        ]);

        foreach (['show_email', 'show_phone', 'show_whatsapp', 'publication_consent'] as $field) {
            $data[$field] = $request->boolean($field);
        }

        return $data;
    }

    private function storePhoto(Request $request, PersonalTrainerSubmission $submission): ?string
    {
        if (! $request->hasFile('photo')) {
            return $submission->photo_path;
        }

        $oldPath = $submission->photo_path;
        $newPath = $request->file('photo')->store('personal-trainers/submissions', 'public');
        $publishedPath = $request->user()->personalTrainer?->photo_path;

        if ($oldPath && $oldPath !== $publishedPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
    }
}
