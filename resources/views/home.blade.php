@extends('layouts.public')

@section('content')
    <section class="section grid items-center gap-10 py-12 lg:grid-cols-[1.1fr_.9fr] lg:py-20">
        <div>
            <p class="mb-4 text-sm font-bold uppercase tracking-[.18em] text-[var(--brand-blue)]">Dream Gym</p>
            <h1 class="max-w-3xl text-4xl font-black leading-tight md:text-6xl">{{ __('site.hero_title') }}</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-neutral-700">{{ __('site.hero_copy') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('bookings.index') }}" class="btn-primary">{{ __('site.book_now') }}</a>
                <a href="#faq" class="btn-secondary">FAQ</a>
            </div>
        </div>
        <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-6 shadow-sm">
            <div class="aspect-[4/3] rounded bg-[var(--brand-blue)] p-8 text-white">
                <div class="flex h-full flex-col justify-between">
                    <div class="text-white/85">Private training room</div>
                    <div>
                        <div class="text-5xl font-black">1h</div>
                        <div class="text-lg">slots de treino</div>
                    </div>
                    <div class="flex justify-between border-t border-white/20 pt-5 text-sm">
                        <span>{{ __('site.price_label') }}</span>
                        <strong>{{ $room ? number_format($room->slot_price_cents / 100, 2, ',', ' ').' '.$room->currency : '12,00 EUR' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-[var(--brand-stone)] bg-white py-14">
        <div class="section">
            <h2 class="max-w-3xl text-3xl font-black">{{ __('site.marketing_title') }}</h2>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ([__('site.benefit_1'), __('site.benefit_2'), __('site.benefit_3')] as $benefit)
                    <div class="rounded-lg border border-[var(--brand-stone)] p-5">
                        <span class="mb-4 block h-2 w-12 rounded bg-[var(--brand-blue)]"></span>
                        <p class="leading-7 text-neutral-700">{{ $benefit }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section py-14">
        <h2 class="text-3xl font-black">{{ __('site.how_it_works_title') }}</h2>
        <div class="mt-8 grid gap-4 md:grid-cols-4">
            @foreach ([__('site.how_it_works_account'), __('site.how_it_works_book'), __('site.how_it_works_access'), __('site.how_it_works_train')] as $index => $step)
                <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-5">
                    <span class="flex h-10 w-10 items-center justify-center rounded bg-[var(--brand-ink)] text-sm font-black text-white">{{ $index + 1 }}</span>
                    <p class="mt-4 font-bold">{{ $step }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="faq" class="section py-14">
        <h2 class="text-3xl font-black">{{ __('site.faq_title') }}</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach (app(\App\Services\ProductCatalog::class)->faq() as $item)
                <div class="rounded-lg bg-white p-5">
                    <h3 class="font-bold">{{ $item['question_'.app()->getLocale()] ?? $item['question_pt'] ?? '' }}</h3>
                    <p class="mt-2 text-neutral-700">{{ $item['answer_'.app()->getLocale()] ?? $item['answer_pt'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
