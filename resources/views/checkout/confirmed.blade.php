@extends('layouts.public')

@section('content')
    <section class="section max-w-3xl py-12">
        <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-8">
            <h1 class="text-3xl font-black">{{ __('site.confirmed_title') }}</h1>
            <p class="mt-3 text-neutral-700">{{ __('site.confirmed_copy') }}</p>
            <div class="mt-8 rounded-lg bg-[var(--brand-blue)] p-6 text-white">
                <div class="text-sm text-white/80">{{ __('site.access_code') }}</div>
                <div class="mt-2 text-5xl font-black tracking-[.2em]">{{ $booking->accessCode?->code }}</div>
                <div class="mt-4 text-sm">{{ __('site.validity') }}: {{ $booking->accessCode?->valid_from->format('H:i') }} - {{ $booking->accessCode?->valid_until->format('H:i') }}</div>
                <div class="mt-2 text-sm text-white/85">{{ __('site.access_code_unique_per_booking') }}</div>
            </div>
            <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">{{ __('site.room') }}</dt><dd class="font-bold">{{ $booking->room->name }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.date') }}</dt><dd class="font-bold">{{ $booking->starts_at->format('d/m/Y') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.time') }}</dt><dd class="font-bold">{{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</dd></div>
                @if ($booking->booking_type === \App\Models\Booking::TYPE_GROUP_HOUR)
                    <div><dt class="text-sm text-neutral-500">{{ __('site.group_capacity') }}</dt><dd class="font-bold">{{ __('site.up_to_people', ['count' => $booking->seats_reserved]) }}</dd></div>
                @endif
            </dl>
        </div>
    </section>
@endsection
