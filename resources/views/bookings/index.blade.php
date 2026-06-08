@extends('layouts.public')

@section('content')
    <section class="section py-10">
        <h1 class="text-4xl font-black">{{ __('site.booking_title') }}</h1>
        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <a href="#booking-form" class="rounded-lg border border-[var(--brand-ink)] bg-[var(--brand-ink)] p-4 text-white">
                <span class="block text-sm text-white/75">{{ __('site.option_book_hour') }}</span>
                <strong class="mt-1 block text-2xl">{{ number_format($room->slot_price_cents / 100, 2, ',', ' ') }} {{ $room->currency }}</strong>
            </a>
            @if ($products['session_pack']['active'])
                <a href="#purchase-form" class="rounded-lg border border-[var(--brand-stone)] bg-white p-4">
                    <span class="block text-sm text-neutral-500">{{ $products['session_pack']['name'] }}</span>
                    <strong class="mt-1 block text-2xl">{{ number_format($products['session_pack']['price_cents'] / 100, 2, ',', ' ') }} {{ $room->currency }}</strong>
                </a>
            @endif
            @if ($products['membership']['active'])
                <a href="#purchase-form" class="rounded-lg border border-[var(--brand-stone)] bg-white p-4">
                    <span class="block text-sm text-neutral-500">{{ $products['membership']['name'] }}</span>
                    <strong class="mt-1 block text-2xl">{{ number_format($products['membership']['price_cents'] / 100, 2, ',', ' ') }} {{ $room->currency }}</strong>
                </a>
            @endif
        </div>

        <form method="GET" class="mt-6 flex max-w-sm gap-3">
            <label class="sr-only" for="date">{{ __('site.choose_date') }}</label>
            <input id="date" name="date" type="date" value="{{ $date }}" class="field">
            <button class="btn-secondary" type="submit">{{ __('site.choose_date') }}</button>
        </form>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_420px]">
            <div>
                <h2 class="mb-4 text-xl font-bold">{{ __('site.available_slots') }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($slots as $slot)
                        <label class="block">
                            <input form="booking-form" type="radio" name="starts_at" value="{{ $slot['starts_at']->toDateTimeString() }}" class="peer sr-only" @disabled(! $slot['available']) required>
                            <span class="block rounded-lg border p-4 text-center font-bold peer-checked:border-[var(--brand-ink)] peer-checked:bg-[var(--brand-ink)] peer-checked:text-white {{ $slot['available'] ? 'cursor-pointer border-[var(--brand-stone)] bg-white' : 'border-neutral-200 bg-neutral-100 text-neutral-400' }}">
                                {{ $slot['starts_at']->format('H:i') }}
                                @if (! $slot['available'])
                                    <small class="block font-normal">{{ __('site.unavailable') }}</small>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <form id="booking-form" method="POST" action="{{ route('bookings.store') }}" class="rounded-lg border border-[var(--brand-stone)] bg-white p-6">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">
                <h2 class="text-xl font-black">{{ __('site.your_details') }}</h2>
                <div class="mt-5 space-y-4">
                    <p class="rounded bg-[var(--brand-cream)] p-3 text-sm font-semibold">{{ __('site.fixed_duration') }}</p>
                    <fieldset>
                        <legend class="text-sm font-bold">{{ __('site.booking_type') }}</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <label class="rounded border border-[var(--brand-stone)] p-3 text-sm font-semibold">
                                <input type="radio" name="booking_type" value="single_hour" checked>
                                {{ __('site.single_hour') }}
                            </label>
                            @if ($products['group_hour']['active'])
                                <label class="rounded border border-[var(--brand-stone)] p-3 text-sm font-semibold">
                                    <input type="radio" name="booking_type" value="group_hour">
                                    {{ __('site.group_hour') }}
                                    <span class="block text-xs font-normal text-neutral-600">{{ number_format($products['group_hour']['price_cents'] / 100, 2, ',', ' ') }} {{ $room->currency }}</span>
                                </label>
                            @endif
                        </div>
                    </fieldset>
                    <label class="block text-sm font-bold">{{ __('site.name') }}<input name="customer_name" class="field mt-1" required></label>
                    <label class="block text-sm font-bold">{{ __('site.email') }}<input name="customer_email" type="email" class="field mt-1" required></label>
                    <label class="block text-sm font-bold">{{ __('site.phone') }}<input name="customer_phone" class="field mt-1"></label>
                    @guest
                        <label class="flex items-center gap-2 text-sm font-bold">
                            <input type="checkbox" name="create_account" value="1">
                            {{ __('site.create_account_with_booking') }}
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-bold">{{ __('site.password') }}<input name="password" type="password" class="field mt-1"></label>
                            <label class="block text-sm font-bold">{{ __('site.password_confirmation') }}<input name="password_confirmation" type="password" class="field mt-1"></label>
                        </div>
                    @else
                        <p class="rounded bg-[var(--brand-cream)] p-3 text-sm">{{ __('site.booking_will_attach') }}</p>
                    @endguest
                </div>
                <button class="btn-primary mt-6 w-full" type="submit">{{ __('site.continue_payment') }}</button>
            </form>
        </div>

        @if ($products['session_pack']['active'] || $products['membership']['active'])
            <form id="purchase-form" method="POST" action="{{ route('purchase.store') }}" class="mt-10 max-w-3xl rounded-lg border border-[var(--brand-stone)] bg-white p-6">
                @csrf
                <h2 class="text-xl font-black">{{ __('site.buy_pack_or_membership') }}</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @if ($products['session_pack']['active'])
                        <label class="rounded border border-[var(--brand-stone)] p-4">
                            <input type="radio" name="product_type" value="session_pack" required>
                            <span class="ml-1 font-bold">{{ $products['session_pack']['name'] }}</span>
                            <span class="block text-sm text-neutral-600">{{ $products['session_pack']['credits'] }} {{ __('site.sessions') }} · {{ number_format($products['session_pack']['price_cents'] / 100, 2, ',', ' ') }} {{ $room->currency }}</span>
                        </label>
                    @endif
                    @if ($products['membership']['active'])
                        <label class="rounded border border-[var(--brand-stone)] p-4">
                            <input type="radio" name="product_type" value="membership" required>
                            <span class="ml-1 font-bold">{{ $products['membership']['name'] }}</span>
                            <span class="block text-sm text-neutral-600">{{ $products['membership']['days'] }} {{ __('site.days') }} · {{ number_format($products['membership']['price_cents'] / 100, 2, ',', ' ') }} {{ $room->currency }}</span>
                        </label>
                    @endif
                </div>

                @guest
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-bold">{{ __('site.name') }}<input name="customer_name" class="field mt-1"></label>
                        <label class="block text-sm font-bold">{{ __('site.email') }}<input name="customer_email" type="email" class="field mt-1"></label>
                        <label class="block text-sm font-bold">{{ __('site.phone') }}<input name="customer_phone" class="field mt-1"></label>
                        <span></span>
                        <label class="block text-sm font-bold">{{ __('site.password') }}<input name="password" type="password" class="field mt-1"></label>
                        <label class="block text-sm font-bold">{{ __('site.password_confirmation') }}<input name="password_confirmation" type="password" class="field mt-1"></label>
                    </div>
                @else
                    <p class="mt-5 rounded bg-[var(--brand-cream)] p-3 text-sm">{{ __('site.purchase_will_attach') }}</p>
                @endguest

                <button class="btn-primary mt-6 w-full" type="submit">{{ __('site.continue_payment') }}</button>
            </form>
        @endif
    </section>
@endsection
