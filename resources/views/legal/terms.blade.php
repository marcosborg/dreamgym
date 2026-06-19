@extends('layouts.public')

@section('content')
    <section class="section max-w-4xl py-12">
        <h1 class="text-4xl font-black">{{ __('site.terms_title') }}</h1>
        <p class="mt-3 text-neutral-700">{{ __('site.terms_intro') }}</p>

        <div class="mt-8 space-y-6 rounded-lg border border-[var(--brand-stone)] bg-white p-8 leading-7 text-neutral-800">
            @foreach ($sections as $section)
                <div>
                    <h2 class="text-xl font-black">{{ $section['title'] }}</h2>
                    <p>{{ $section['body'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
