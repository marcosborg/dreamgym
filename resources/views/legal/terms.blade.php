@extends('layouts.public')

@section('content')
    <section class="section max-w-4xl py-12">
        <h1 class="text-4xl font-black">{{ __('site.terms_title') }}</h1>
        <p class="mt-3 text-neutral-700">{{ __('site.terms_intro') }}</p>

        <div class="mt-8 space-y-6 rounded-lg border border-[var(--brand-stone)] bg-white p-8 leading-7 text-neutral-800">
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_booking_title') }}</h2>
                <p>{{ __('site.terms_booking_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_access_title') }}</h2>
                <p>{{ __('site.terms_access_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_payment_title') }}</h2>
                <p>{{ __('site.terms_payment_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_cancellation_title') }}</h2>
                <p>{{ __('site.terms_cancellation_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_children_title') }}</h2>
                <p>{{ __('site.terms_children_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_use_title') }}</h2>
                <p>{{ __('site.terms_use_body') }}</p>
            </div>
            <div>
                <h2 class="text-xl font-black">{{ __('site.terms_contact_title') }}</h2>
                <p>{{ __('site.terms_contact_body') }}</p>
            </div>
        </div>
    </section>
@endsection
