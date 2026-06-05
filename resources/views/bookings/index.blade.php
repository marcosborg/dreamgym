@extends('layouts.public')

@section('content')
    <section class="section py-10">
        <h1 class="text-4xl font-black">{{ __('site.booking_title') }}</h1>
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
    </section>
@endsection
