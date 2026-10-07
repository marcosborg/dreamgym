@extends('layouts.public')

@section('content')
    <section class="section min-w-0 py-12">
        <p class="eyebrow">{{ __('site.my_account') }}</p>
        <h1 class="mt-3 break-words text-4xl font-black md:text-6xl">{{ app()->getLocale() === 'pt' ? 'Olá' : 'Hello' }}, <span class="red-word">{{ auth()->user()->name }}</span></h1>
        <p class="mt-2 text-neutral-700">{{ __('site.booking_history') }}</p>
        <p class="mt-2 text-sm text-neutral-600">{{ __('site.cancellation_policy_short') }}</p>
        @if (session('status'))
            <div class="mt-4 rounded border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="mt-8 grid gap-4 md:grid-cols-2">
            <div class="dark-panel border-[var(--brand-blue)] p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm text-neutral-400">{{ __('site.session_credits') }}</div>
                        <div class="mt-2 text-3xl font-black">{{ auth()->user()->session_credits }}</div>
                    </div>
                    <div class="border-l border-[var(--brand-stone)] pl-4 text-right">
                        <div class="text-sm text-neutral-400">{{ __('site.session_credit_validity') }}</div>
                        <div class="mt-1 font-bold">{{ ! empty($sessionCreditValidity) ? $sessionCreditValidity[0]['expires_at']->format('d/m/Y') : '—' }}</div>
                        <div class="mt-1 text-xs text-neutral-400">{{ __('site.session_credit_duration') }}</div>
                    </div>
                </div>
                @if ($undatedSessionCredits > 0)
                    <p class="mt-4 text-sm text-neutral-400">{{ __('site.credits_date_unavailable', ['count' => $undatedSessionCredits]) }}</p>
                @endif
                @if (! empty($sessionCreditValidity))
                    <div class="mt-4 border-t border-[var(--brand-stone)] pt-3 text-sm">
                        @foreach ($sessionCreditValidity as $validity)
                            <p>{{ trans_choice('site.credits_expiry', $validity['credits'], ['count' => $validity['credits'], 'date' => $validity['expires_at']->format('d/m/Y')]) }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="dark-panel p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm text-neutral-400">{{ __('site.membership_credits') }}</div>
                        <div class="mt-2 text-3xl font-black">{{ auth()->user()->hasActiveMembership() ? auth()->user()->membership_credits : 0 }}</div>
                    </div>
                    <div class="border-l border-[var(--brand-stone)] pl-4 text-right">
                        <div class="text-sm text-neutral-400">{{ __('site.membership_valid_until') }}</div>
                        <div class="mt-1 font-bold">{{ auth()->user()->membership_expires_at?->format('d/m/Y') ?? '—' }}</div>
                        <div class="mt-1 text-xs text-neutral-400">{{ __('site.membership_duration') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 rounded-lg border border-[var(--brand-stone)] bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black">{{ __('site.pt_profile_title') }}</h2>
                    <p class="mt-1 text-sm text-neutral-600">
                        @if ($trainerSubmission)
                            {{ __('site.pt_current_status') }}: <strong>{{ $trainerSubmission->statusLabel() }}</strong>
                        @else
                            {{ __('site.pt_profile_intro') }}
                        @endif
                    </p>
                </div>
                <a class="btn-primary" href="{{ route('account.personal-trainer.edit') }}">
                    {{ $trainerSubmission ? __('site.pt_view_application') : __('site.pt_apply') }}
                </a>
            </div>
        </div>

        <div class="mt-8 grid min-w-0 gap-4 lg:hidden">
            @forelse ($bookings as $booking)
                <article class="min-w-0 rounded-lg border border-[var(--brand-stone)] bg-white p-4">
                    <h2 class="font-bold">{{ $booking->starts_at->format('d/m/Y') }} · {{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</h2>
                    <p class="mt-1 text-xs">#{{ $booking->id }}</p>
                    <dl class="mt-4 grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-x-4 gap-y-3 text-sm [&_dd]:min-w-0 [&_dd]:break-words">
                        <dt>{{ __('site.room') }}</dt><dd>{{ $booking->room->localized_name }}</dd>
                        <dt>Status</dt><dd>{{ $booking->status }}</dd>
                        <dt>{{ __('site.booking_type') }}</dt><dd>{{ $booking->booking_type }}</dd>
                        <dt>{{ __('site.access_code') }}</dt><dd class="font-bold">{{ $booking->accessCode?->ready_for_use ? $booking->accessCode->display_code : ($booking->status === 'confirmed' ? __('site.access_preparing') : '-') }}</dd>
                        <dt>{{ __('site.price_label') }}</dt><dd>{{ $booking->formatted_price }}</dd>
                    </dl>
                    @include('partials.resume-booking-payment')
                    @if ($booking->canBeCancelledByCustomer())
                        <form class="mt-4" method="POST" action="{{ route('account.bookings.cancel', $booking) }}">
                            @csrf
                            <button class="btn-secondary w-full" type="submit">{{ __('site.cancel_booking') }}</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="rounded-lg border border-[var(--brand-stone)] bg-white p-4 text-sm text-neutral-600">{{ __('site.no_bookings') }}</p>
            @endforelse
        </div>

        <div class="mt-8 hidden overflow-x-auto rounded-lg border border-[var(--brand-stone)] bg-white lg:block">
            <table class="w-full text-left text-sm">
                <thead class="bg-[var(--brand-cream)]">
                <tr>
                    <th class="p-4">{{ __('site.date') }}</th>
                    <th class="p-4">{{ __('site.time') }}</th>
                    <th class="p-4">{{ __('site.room') }}</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">{{ __('site.booking_type') }}</th>
                    <th class="p-4">{{ __('site.access_code') }}</th>
                    <th class="p-4">{{ __('site.price_label') }}</th>
                    <th class="p-4"></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($bookings as $booking)
                    <tr class="border-t border-[var(--brand-stone)]">
                        <td class="p-4">{{ $booking->starts_at->format('d/m/Y') }}<br><span class="text-xs">#{{ $booking->id }}</span></td>
                        <td class="p-4">{{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</td>
                        <td class="p-4">{{ $booking->room->localized_name }}</td>
                        <td class="p-4">{{ $booking->status }}</td>
                        <td class="p-4">{{ $booking->booking_type }}</td>
                        <td class="p-4 font-bold">{{ $booking->accessCode?->ready_for_use ? $booking->accessCode->display_code : ($booking->status === 'confirmed' ? __('site.access_preparing') : '-') }}</td>
                        <td class="p-4">{{ $booking->formatted_price }}</td>
                        <td class="p-4 text-right">
                            @include('partials.resume-booking-payment')
                            @if ($booking->canBeCancelledByCustomer())
                                <form method="POST" action="{{ route('account.bookings.cancel', $booking) }}">
                                    @csrf
                                    <button class="btn-secondary" type="submit">{{ __('site.cancel_booking') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-4 text-neutral-600">{{ __('site.no_bookings') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $bookings->links() }}</div>
    </section>
@endsection
