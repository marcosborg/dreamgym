@extends('layouts.public')

@section('content')
    <section class="section max-w-3xl py-12">
        <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-8">
            <h1 class="text-3xl font-black">{{ __('site.purchase_confirmed_title') }}</h1>
            <p class="mt-3 text-neutral-700">{{ __('site.purchase_confirmed_copy') }}</p>
            <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">{{ __('site.product') }}</dt><dd class="font-bold">{{ $payment->metadata['label'] ?? $payment->product_type }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.price_label') }}</dt><dd class="font-bold">{{ number_format($payment->amount_cents / 100, 2, ',', ' ') }} {{ $payment->currency }}</dd></div>
            </dl>
            <a href="{{ route('account.dashboard') }}" class="btn-primary mt-8 inline-flex">{{ __('site.my_account') }}</a>
        </div>
    </section>
@endsection
