@extends('layouts.public')

@section('content')
    <section class="section max-w-4xl py-12">
        <h1 class="text-4xl font-black">{{ __('site.privacy_title') }}</h1>
        <p class="mt-3 text-neutral-700">{{ __('site.privacy_intro') }}</p>

        <div class="mt-8 space-y-6 rounded-lg border border-[var(--brand-stone)] bg-white p-8 leading-7 text-neutral-800">
            <div>
                <h2 class="text-xl font-black">{{ __('site.privacy_data_title') }}</h2>
                <p>{{ __('site.privacy_data_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.privacy_use_title') }}</h2>
                <p>{{ __('site.privacy_use_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.privacy_storage_title') }}</h2>
                <p>{{ __('site.privacy_storage_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.privacy_rights_title') }}</h2>
                <p>{{ __('site.privacy_rights_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.privacy_contact_title') }}</h2>
                <p>{{ __('site.privacy_contact_body') }}</p>
            </div>
        </div>
    </section>
@endsection
