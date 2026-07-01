@extends('layouts.public')

@section('content')
    @php
        $defaultPurchaseProduct = $purchaseProducts->first();
    @endphp

    <section class="section py-10">
        <h1 class="text-4xl font-black">{{ __('site.booking_title') }}</h1>
        <div class="mt-6 grid gap-3 md:grid-cols-4">
            <a href="#booking-form" data-booking-card data-booking-type="single_hour" data-select-booking-type="single_hour" class="option-card is-selected">
                <span class="option-marker" aria-hidden="true"></span>
                <span class="block text-sm text-white/75">{{ __('site.option_book_hour') }}</span>
                <strong class="mt-1 block text-2xl">{{ number_format($products['single_hour']['price_cents'] / 100, 2, ',', ' ') }} {{ $products['single_hour']['currency'] }}</strong>
            </a>
            @if ($products['group_hour']['active'])
                <a href="#booking-form" data-booking-card data-booking-type="group_hour" data-select-booking-type="group_hour" class="option-card">
                    <span class="option-marker" aria-hidden="true"></span>
                    <span class="block text-sm text-neutral-500">{{ $products['group_hour']['name'] }}</span>
                    <strong class="mt-1 block text-2xl">{{ number_format($products['group_hour']['price_cents'] / 100, 2, ',', ' ') }} {{ $products['group_hour']['currency'] }}</strong>
                    <span class="mt-1 block text-sm text-neutral-600">{{ __('site.up_to_people', ['count' => $products['group_hour']['seats'] ?? $room->capacity]) }}</span>
                </a>
            @endif
            @foreach ($purchaseProducts as $product)
                <a
                    href="#purchase-form"
                    data-product-card
                    data-product-id="{{ $product['id'] }}"
                    data-select-product="{{ $product['id'] }}"
                    data-product-name="{{ $product['name'] }}"
                    data-product-detail="@if ($product['credits']) {{ $product['credits'] }} {{ __('site.sessions') }} @elseif ($product['days']) {{ $product['days'] }} {{ __('site.days') }} @endif"
                    data-product-price="{{ number_format($product['price_cents'] / 100, 2, ',', ' ') }} {{ $product['currency'] }}"
                    class="option-card"
                >
                    <span class="option-marker" aria-hidden="true"></span>
                    <span class="block text-sm text-neutral-500">{{ $product['name'] }}</span>
                    <strong class="mt-1 block text-2xl">{{ number_format($product['price_cents'] / 100, 2, ',', ' ') }} {{ $product['currency'] }}</strong>
                </a>
            @endforeach
        </div>

        <form method="GET" class="mt-6 flex max-w-sm gap-3">
            <label class="sr-only" for="date">{{ __('site.choose_date') }}</label>
            <input id="date" name="date" type="date" value="{{ $date }}" class="field">
            <button class="btn-secondary" type="submit">{{ __('site.choose_date') }}</button>
        </form>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_420px]">
            <div>
                <h2 class="mb-4 text-xl font-bold">{{ __('site.available_hours') }}</h2>
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
                            <label data-booking-option="single_hour" class="option-choice is-selected">
                                <input type="radio" name="booking_type" value="single_hour" class="sr-only" checked>
                                <span class="option-marker" aria-hidden="true"></span>
                                {{ __('site.single_hour') }}
                            </label>
                            @if ($products['group_hour']['active'])
                                <label data-booking-option="group_hour" class="option-choice">
                                    <input type="radio" name="booking_type" value="group_hour" class="sr-only">
                                    <span class="option-marker" aria-hidden="true"></span>
                                    {{ __('site.group_hour') }}
                                    <span class="block text-xs font-normal text-neutral-600">{{ number_format($products['group_hour']['price_cents'] / 100, 2, ',', ' ') }} {{ $products['group_hour']['currency'] }} · {{ __('site.up_to_people', ['count' => $products['group_hour']['seats'] ?? $room->capacity]) }}</span>
                                </label>
                            @endif
                        </div>
                    </fieldset>
                    <label class="block text-sm font-bold">{{ __('site.name') }}<input name="customer_name" class="field mt-1" required></label>
                    <label class="block text-sm font-bold">{{ __('site.email') }}<input name="customer_email" type="email" class="field mt-1" required></label>
                    <label class="block text-sm font-bold">{{ __('site.phone') }}<input name="customer_phone" class="field mt-1"></label>
                    <fieldset>
                        <legend class="text-sm font-bold">{{ __('site.bringing_children_question') }}</legend>
                        <div class="mt-2 flex gap-4 text-sm font-semibold">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="bringing_children" value="0" checked data-children-toggle>
                                {{ __('site.no') }}
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="bringing_children" value="1" data-children-toggle>
                                {{ __('site.yes') }}
                            </label>
                        </div>
                    </fieldset>
                    <div id="children-responsibility" class="hidden rounded border border-[var(--brand-stone)] p-3 text-sm font-semibold">
                        <label class="flex items-start gap-2">
                            <input class="mt-1" type="checkbox" name="children_responsibility_accepted" value="1">
                            <span class="no-underline decoration-transparent">{{ __('site.children_responsibility_acceptance') }}</span>
                        </label>
                    </div>
                    <label class="flex items-start gap-2 text-sm font-bold">
                        <input type="checkbox" name="terms_accepted" value="1">
                        <span>{!! __('site.terms_acceptance_html', ['url' => route('legal.terms')]) !!}</span>
                    </label>
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

        @if ($purchaseProducts->isNotEmpty())
            <form id="purchase-form" method="POST" action="{{ route('purchase.store') }}" class="mt-10 max-w-3xl rounded-lg border border-[var(--brand-stone)] bg-white p-6">
                @csrf
                <h2 class="text-xl font-black">{{ __('site.buy_pack_or_membership') }}</h2>
                <div class="mt-5 rounded-lg border border-[var(--brand-blue)] bg-[var(--brand-cream)] p-4" data-product-summary>
                    <span class="block text-sm font-semibold text-neutral-600">{{ __('site.product') }}</span>
                    <strong class="mt-1 block text-xl" data-product-summary-name>{{ $defaultPurchaseProduct['name'] ?? '' }}</strong>
                    <span class="block text-sm text-neutral-700" data-product-summary-detail>
                        @if ($defaultPurchaseProduct)
                            @if ($defaultPurchaseProduct['credits'])
                                {{ $defaultPurchaseProduct['credits'] }} {{ __('site.sessions') }} ·
                            @elseif ($defaultPurchaseProduct['days'])
                                {{ $defaultPurchaseProduct['days'] }} {{ __('site.days') }} ·
                            @endif
                            {{ number_format($defaultPurchaseProduct['price_cents'] / 100, 2, ',', ' ') }} {{ $defaultPurchaseProduct['currency'] }}
                        @endif
                    </span>
                </div>

                <div class="hidden">
                    @foreach ($purchaseProducts as $product)
                        <input type="radio" name="product_id" value="{{ $product['id'] }}" required @checked(($defaultPurchaseProduct['id'] ?? null) === $product['id'])>
                    @endforeach
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
