<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="brand-shell">
    <header class="section flex items-center justify-between py-5">
        <a href="{{ route('home') }}" class="flex items-center">
            <img src="{{ asset('brand/logo.png') }}" alt="Dream Gym" class="h-24 w-auto">
        </a>
        <nav class="flex items-center gap-3 text-sm font-semibold">
            <a href="{{ route('bookings.index') }}">{{ __('site.nav_book') }}</a>
            <a href="{{ url('/admin') }}">{{ __('site.nav_admin') }}</a>
            @auth
                <a href="{{ route('account.dashboard') }}">{{ __('site.my_account') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">{{ __('site.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}">{{ __('site.login') }}</a>
            @endauth
            <a href="{{ route('locale.switch', app()->getLocale() === 'pt' ? 'en' : 'pt') }}" class="rounded border border-[var(--brand-stone)] px-3 py-1">
                {{ app()->getLocale() === 'pt' ? 'EN' : 'PT' }}
            </a>
        </nav>
    </header>
    <main>
        @if ($errors->any())
            <div class="section mb-6 rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="section flex flex-wrap items-center justify-between gap-3 border-t border-[var(--brand-stone)] py-8 text-sm text-neutral-600">
        <div class="flex items-center gap-3">
            <img src="{{ asset('brand/logo.png') }}" alt="Dream Gym" class="h-12 w-auto">
            <span>&copy; {{ date('Y') }} Dream Gym</span>
        </div>
        <nav class="flex gap-4 font-semibold">
            <a href="{{ route('home') }}#faq">FAQ</a>
            <a href="{{ route('legal.terms') }}">{{ __('site.terms') }}</a>
            <a href="{{ route('legal.privacy') }}">{{ __('site.privacy') }}</a>
        </nav>
    </footer>
</div>
</body>
</html>
