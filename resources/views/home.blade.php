@extends('layouts.public')

@section('content')
    <section class="section grid items-center gap-10 py-12 lg:grid-cols-[1.1fr_.9fr] lg:py-20">
        <div>
            <p class="mb-4 text-sm font-bold uppercase tracking-[.18em] text-[var(--brand-blue)]">Dream Gym</p>
            <h1 class="max-w-3xl text-4xl font-black leading-tight md:text-6xl">{{ __('site.hero_title') }}</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-neutral-700">{{ __('site.hero_copy') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('bookings.index') }}" class="btn-primary btn-primary-lg">{{ __('site.book_now') }}</a>
            </div>
        </div>
        <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-6 shadow-sm">
            <div class="aspect-[4/3] rounded bg-[var(--brand-blue)] p-8 text-white">
                <div class="flex h-full flex-col justify-between">
                    <div class="text-white/85">Private training room</div>
                    <div>
                        <div class="text-5xl font-black">1h</div>
                        <div class="text-lg">{{ __('site.training_hours') }}</div>
                    </div>
                    <div class="flex justify-between border-t border-white/20 pt-5 text-sm">
                        <span>{{ __('site.price_label') }}</span>
                        <strong>{{ $singleHour ? number_format($singleHour['price_cents'] / 100, 2, ',', ' ').' '.$singleHour['currency'] : '12,00 EUR' }}</strong>
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
            @foreach ([
                [__('site.how_it_works_account'), __('site.how_it_works_account_desc')],
                [__('site.how_it_works_book'), __('site.how_it_works_book_desc')],
                [__('site.how_it_works_access'), __('site.how_it_works_access_desc')],
                [__('site.how_it_works_train'), __('site.how_it_works_train_desc')],
            ] as $index => [$step, $description])
                <div class="rounded-lg border border-[var(--brand-stone)] bg-white p-5">
                    <span class="flex h-10 w-10 items-center justify-center rounded bg-[var(--brand-ink)] text-sm font-black text-white">{{ $index + 1 }}</span>
                    <p class="mt-4 font-bold">{{ $step }}</p>
                    <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @if ($personalTrainers->isNotEmpty())
        <section class="border-y border-[var(--brand-stone)] bg-white py-14">
            <div class="section">
                <h2 class="text-3xl font-black">{{ __('site.personal_trainers_title') }}</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($personalTrainers as $trainer)
                        <article class="rounded-xl border border-[var(--brand-stone)] p-6 shadow-sm">
                            <div class="flex items-center gap-4">
                                @if ($trainer->photo_url)
                                    <img src="{{ $trainer->photo_url }}" alt="{{ $trainer->name }}" class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-[var(--brand-cream)]">
                                @else
                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[var(--brand-blue)] text-xl font-black text-white ring-4 ring-[var(--brand-cream)]" aria-label="{{ $trainer->name }}">
                                        {{ $trainer->initials }}
                                    </div>
                                @endif
                                <div>
                                    <h3 class="text-xl font-black">{{ $trainer->name }}</h3>
                                    @if ($trainer->localized('title'))
                                        <p class="mt-1 font-semibold text-[var(--brand-blue)]">{{ $trainer->localized('title') }}</p>
                                    @endif
                                </div>
                            </div>
                            @if ($trainer->localized('specialties'))
                                <p class="mt-5 text-sm font-bold uppercase tracking-wide text-neutral-500">{{ $trainer->localized('specialties') }}</p>
                            @endif
                            @if ($trainer->localized('bio'))
                                <p class="mt-3 leading-7 text-neutral-700">{{ $trainer->localized('bio') }}</p>
                            @endif
                            @if (($trainer->show_email && $trainer->email) || ($trainer->show_phone && $trainer->phone) || ($trainer->show_whatsapp && $trainer->whatsapp_url))
                                <div class="mt-5 flex flex-wrap gap-2 text-sm font-bold">
                                    @if ($trainer->show_email && $trainer->email)
                                        <a class="btn-secondary" href="mailto:{{ $trainer->email }}">{{ __('site.pt_contact_email') }}</a>
                                    @endif
                                    @if ($trainer->show_phone && $trainer->phone)
                                        <a class="btn-secondary" href="tel:{{ $trainer->phone }}">{{ __('site.pt_contact_phone') }}</a>
                                    @endif
                                    @if ($trainer->show_whatsapp && $trainer->whatsapp_url)
                                        <a class="btn-primary" href="{{ $trainer->whatsapp_url }}" target="_blank" rel="noopener">{{ __('site.pt_contact_whatsapp') }}</a>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

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
