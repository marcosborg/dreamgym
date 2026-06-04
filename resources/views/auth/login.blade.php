@extends('layouts.public')

@section('content')
    <section class="section max-w-md py-12">
        <form method="POST" action="{{ route('login.store') }}" class="rounded-lg border border-[var(--brand-stone)] bg-white p-8">
            @csrf
            <h1 class="text-3xl font-black">{{ __('site.login') }}</h1>
            <div class="mt-6 space-y-4">
                <label class="block text-sm font-bold">{{ __('site.email') }}<input name="email" type="email" class="field mt-1" required></label>
                <label class="block text-sm font-bold">{{ __('site.password') }}<input name="password" type="password" class="field mt-1" required></label>
            </div>
            <button class="btn-primary mt-6 w-full" type="submit">{{ __('site.login') }}</button>
            <p class="mt-4 text-sm"><a class="font-bold" href="{{ route('register') }}">{{ __('site.register') }}</a></p>
        </form>
    </section>
@endsection
