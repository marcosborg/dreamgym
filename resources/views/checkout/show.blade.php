@extends('layouts.public')

@section('content')
    <section class="section max-w-3xl py-12">
        <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-8">
            <h1 class="text-3xl font-black">{{ __('site.checkout_title') }}</h1>
            <p class="mt-3 text-neutral-700">{{ __('site.checkout_copy') }}</p>
            <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">{{ __('site.room') }}</dt><dd class="font-bold">{{ $booking->room->name }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.date') }}</dt><dd class="font-bold">{{ $booking->starts_at->format('d/m/Y') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.time') }}</dt><dd class="font-bold">{{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.price_label') }}</dt><dd class="font-bold">{{ $booking->formatted_price }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('checkout.complete', $booking) }}" class="mt-8">
                @csrf
                <label class="mb-5 flex items-start gap-2 text-sm font-bold">
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <span>{!! __('site.terms_acceptance_html', ['url' => route('legal.terms')]) !!}</span>
                </label>
                <button class="btn-primary w-full" type="submit">{{ __('site.pay_now') }}</button>
            </form>
        </div>
    </section>
@endsection
