@extends('layouts.public')

@section('content')
    <section class="section py-12">
        <h1 class="text-4xl font-black">{{ __('site.my_account') }}</h1>
        <p class="mt-2 text-neutral-700">{{ __('site.booking_history') }}</p>

        <div class="mt-8 overflow-hidden rounded-lg border border-[var(--brand-stone)] bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-[var(--brand-cream)]">
                <tr>
                    <th class="p-4">{{ __('site.date') }}</th>
                    <th class="p-4">{{ __('site.time') }}</th>
                    <th class="p-4">{{ __('site.room') }}</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">{{ __('site.access_code') }}</th>
                    <th class="p-4">{{ __('site.price_label') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($bookings as $booking)
                    <tr class="border-t border-[var(--brand-stone)]">
                        <td class="p-4">{{ $booking->starts_at->format('d/m/Y') }}</td>
                        <td class="p-4">{{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</td>
                        <td class="p-4">{{ $booking->room->name }}</td>
                        <td class="p-4">{{ $booking->status }}</td>
                        <td class="p-4 font-bold">{{ $booking->accessCode?->code ?? '-' }}</td>
                        <td class="p-4">{{ $booking->formatted_price }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-neutral-600">{{ __('site.no_bookings') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $bookings->links() }}</div>
    </section>
@endsection
