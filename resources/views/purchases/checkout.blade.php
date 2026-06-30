@extends('layouts.public')

@section('content')
    <section class="section max-w-3xl py-12">
        <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-8">
            <h1 class="text-3xl font-black">{{ __('site.checkout_title') }}</h1>
            <p class="mt-3 text-neutral-700">{{ __('site.checkout_copy') }}</p>
            <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">{{ __('site.product') }}</dt><dd class="font-bold">{{ $payment->metadata['label'] ?? $payment->product_type }}</dd></div>
                <div><dt class="text-sm text-neutral-500">{{ __('site.price_label') }}</dt><dd class="font-bold">{{ number_format($payment->amount_cents / 100, 2, ',', ' ') }} {{ $payment->currency }}</dd></div>
            </dl>
            @if (session('status'))
                <div class="mt-6 rounded border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-900">{{ session('status') }}</div>
            @endif
            @if (($payment->metadata['payment_method'] ?? null) === 'multibanco')
                <div class="mt-6 rounded-lg border border-[var(--brand-stone)] bg-neutral-50 p-5">
                    <h2 class="text-lg font-black">{{ __('site.multibanco_reference') }}</h2>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div><dt class="text-sm text-neutral-500">{{ __('site.entity') }}</dt><dd class="font-bold">{{ $payment->metadata['ifthenpay']['entity'] ?? '' }}</dd></div>
                        <div><dt class="text-sm text-neutral-500">{{ __('site.reference') }}</dt><dd class="font-bold">{{ $payment->metadata['ifthenpay']['reference'] ?? '' }}</dd></div>
                        <div><dt class="text-sm text-neutral-500">{{ __('site.amount') }}</dt><dd class="font-bold">{{ number_format($payment->amount_cents / 100, 2, ',', ' ') }} {{ $payment->currency }}</dd></div>
                    </dl>
                    <p class="mt-4 text-sm text-neutral-700">{{ __('site.payment_waiting_callback') }}</p>
                </div>
            @elseif (($payment->metadata['payment_method'] ?? null) === 'mbway')
                <div class="mt-6 rounded-lg border border-[var(--brand-stone)] bg-neutral-50 p-5">
                    <h2 class="text-lg font-black">{{ __('site.mbway_request_sent') }}</h2>
                    <p class="mt-2 text-sm text-neutral-700">{{ __('site.payment_waiting_callback') }}</p>
                </div>
            @endif
            <form method="POST" action="{{ route('purchase.complete', $payment) }}" class="mt-8">
                @csrf
                @if ($paymentProvider === 'ifthenpay')
                    <div class="mb-5 grid gap-3 sm:grid-cols-2">
                        <label class="rounded border border-[var(--brand-stone)] p-4 font-bold">
                            <input type="radio" name="payment_method" value="multibanco" @checked(old('payment_method', 'multibanco') === 'multibanco')>
                            <span class="ml-2">Multibanco</span>
                        </label>
                        <label class="rounded border border-[var(--brand-stone)] p-4 font-bold">
                            <input type="radio" name="payment_method" value="mbway" @checked(old('payment_method') === 'mbway')>
                            <span class="ml-2">MB WAY</span>
                        </label>
                    </div>
                    <label class="mb-5 block">
                        <span class="text-sm font-bold">{{ __('site.mbway_phone') }}</span>
                        <input class="mt-2 w-full rounded border border-[var(--brand-stone)] px-4 py-3" name="mbway_phone" value="{{ old('mbway_phone', $payment->user?->phone) }}" placeholder="912345678">
                    </label>
                    @error('payment_method')<p class="mb-4 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                    @error('mbway_phone')<p class="mb-4 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                @endif
                <label class="mb-5 flex items-start gap-2 text-sm font-bold">
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <span>{!! __('site.terms_acceptance_html', ['url' => route('legal.terms')]) !!}</span>
                </label>
                <button class="btn-primary w-full" type="submit">{{ __('site.pay_now') }}</button>
            </form>
        </div>
    </section>
@endsection
