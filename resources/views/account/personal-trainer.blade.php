@extends('layouts.public')

@section('content')
    @php($locked = $submission && $submission->exists && ! $submission->isEditable())
    <section class="section max-w-5xl py-12">
        <div class="mb-8">
            <a class="text-sm font-bold text-[var(--brand-blue)]" href="{{ route('account.dashboard') }}">&larr; {{ __('site.my_account') }}</a>
            <h1 class="mt-3 text-4xl font-black">{{ __('site.pt_application_title') }}</h1>
            <p class="mt-2 max-w-3xl text-neutral-700">{{ __('site.pt_application_intro') }}</p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($submission?->exists)
            <div class="mb-6 rounded-lg border border-[var(--brand-stone)] bg-white p-5">
                <span class="text-sm text-neutral-500">{{ __('site.pt_current_status') }}</span>
                <strong class="ml-2">{{ $submission->statusLabel() }}</strong>
                @if ($submission->review_note)
                    <div class="mt-3 rounded bg-amber-50 p-4 text-sm text-amber-900">
                        <strong>{{ __('site.pt_review_note') }}:</strong> {{ $submission->review_note }}
                    </div>
                @endif
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" class="rounded-lg border border-[var(--brand-stone)] bg-white p-6 md:p-8">
            @csrf
            <fieldset @disabled($locked)>
                <div class="grid gap-6 md:grid-cols-2">
                    <label class="block text-sm font-bold md:col-span-2">{{ __('site.name') }}
                        <input class="field mt-1" name="name" maxlength="120" value="{{ old('name', $submission?->name ?? auth()->user()->name) }}">
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.pt_title_pt') }}
                        <input class="field mt-1" name="title_pt" maxlength="160" value="{{ old('title_pt', $submission?->title_pt) }}">
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.pt_title_en') }}
                        <input class="field mt-1" name="title_en" maxlength="160" value="{{ old('title_en', $submission?->title_en) }}">
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.pt_specialties_pt') }}
                        <textarea class="field mt-1" name="specialties_pt" rows="3">{{ old('specialties_pt', $submission?->specialties_pt) }}</textarea>
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.pt_specialties_en') }}
                        <textarea class="field mt-1" name="specialties_en" rows="3">{{ old('specialties_en', $submission?->specialties_en) }}</textarea>
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.pt_bio_pt') }}
                        <textarea class="field mt-1" name="bio_pt" rows="7">{{ old('bio_pt', $submission?->bio_pt) }}</textarea>
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.pt_bio_en') }}
                        <textarea class="field mt-1" name="bio_en" rows="7">{{ old('bio_en', $submission?->bio_en) }}</textarea>
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.email') }}
                        <input class="field mt-1" type="email" name="email" value="{{ old('email', $submission?->email ?? auth()->user()->email) }}">
                    </label>
                    <label class="block text-sm font-bold">{{ __('site.phone') }}
                        <input class="field mt-1" name="phone" value="{{ old('phone', $submission?->phone ?? auth()->user()->phone) }}">
                    </label>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold">{{ __('site.pt_photo') }}</label>
                        <div class="mt-2 flex flex-wrap items-center gap-5">
                            @if ($submission?->photo_url)
                                <img src="{{ $submission->photo_url }}" alt="" class="h-24 w-24 rounded-full object-cover">
                            @endif
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="text-sm">
                        </div>
                        <p class="mt-2 text-xs text-neutral-500">{{ __('site.pt_photo_help') }}</p>
                    </div>
                    <div class="space-y-3 md:col-span-2">
                        <p class="text-sm font-bold">{{ __('site.pt_public_contacts') }}</p>
                        @foreach ([['show_email', 'pt_show_email'], ['show_phone', 'pt_show_phone'], ['show_whatsapp', 'pt_show_whatsapp']] as [$field, $label])
                            <label class="flex items-center gap-3 text-sm">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $submission?->{$field} ?? ($field !== 'show_whatsapp')))>
                                {{ __('site.'.$label) }}
                            </label>
                        @endforeach
                        <label class="flex items-start gap-3 text-sm font-semibold">
                            <input type="hidden" name="publication_consent" value="0">
                            <input class="mt-1" type="checkbox" name="publication_consent" value="1" @checked(old('publication_consent', $submission?->publication_consent))>
                            <span>{{ __('site.pt_publication_consent') }}</span>
                        </label>
                    </div>
                </div>
            </fieldset>

            @if (! $locked)
                <div class="mt-8 flex flex-wrap gap-3">
                    <button class="btn-secondary" formaction="{{ route('account.personal-trainer.save') }}" type="submit">{{ __('site.pt_save_draft') }}</button>
                    <button class="btn-primary" formaction="{{ route('account.personal-trainer.submit') }}" type="submit">{{ __('site.pt_submit') }}</button>
                </div>
            @else
                <p class="mt-8 text-sm font-semibold text-neutral-600">{{ __('site.pt_submission_locked') }}</p>
            @endif
        </form>
    </section>
@endsection
