@extends('layouts.public')

@section('content')
    <section class="hero-section">
        <div class="section grid min-h-[660px] items-center gap-10 py-16 lg:grid-cols-[1.08fr_.92fr] lg:py-24">
            <div>
                <p class="eyebrow mb-5">Dream Gym Private</p>
                <h1 class="max-w-3xl text-5xl font-black leading-[1.04] md:text-7xl">
                    {{ app()->getLocale() === 'pt' ? 'A tua sala de treino' : 'Your private training room' }}
                    <span class="red-word block">{{ app()->getLocale() === 'pt' ? 'privada,' : 'booked' }}</span>
                    {{ app()->getLocale() === 'pt' ? 'reservada de hora a hora.' : 'by the hour.' }}
                </h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-neutral-300">{{ __('site.hero_copy') }}</p>
                <a href="{{ route('bookings.index') }}" class="btn-primary btn-primary-lg mt-9">{{ __('site.book_now') }} <span class="ml-3">→</span></a>
            </div>
            <div class="dark-panel mx-auto w-full max-w-lg border-[var(--brand-blue)] p-8 lg:mt-28">
                <div class="eyebrow">Private training room</div>
                <div class="mt-12 text-6xl font-black">1h</div>
                <div class="text-lg text-neutral-300">{{ __('site.training_hours') }}</div>
                <div class="mt-14 flex justify-between border-t border-[var(--brand-stone)] pt-6 text-sm">
                    <span class="text-neutral-400">{{ __('site.price_label') }}</span>
                    <strong class="text-xl text-[var(--brand-blue)]">{{ $singleHour ? number_format($singleHour['price_cents'] / 100, 2, ',', ' ').' '.$singleHour['currency'] : '8,00 EUR' }}</strong>
                </div>
            </div>
        </div>
        <div class="section grid gap-px border-t border-[var(--brand-stone)] sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['24/7', app()->getLocale() === 'pt' ? 'No teu horário' : 'On your schedule'],
                [__('site.nav_book'), app()->getLocale() === 'pt' ? 'Online em segundos' : 'Online in seconds'],
                [__('site.access_code'), app()->getLocale() === 'pt' ? 'Único e temporário' : 'Unique and temporary'],
                [app()->getLocale() === 'pt' ? 'Espaço privado' : 'Private space', app()->getLocale() === 'pt' ? 'Treina à tua maneira' : 'Train your way'],
            ] as [$label, $copy])
                <div class="px-6 py-7 lg:border-r lg:border-[var(--brand-stone)]">
                    <strong class="block uppercase tracking-wide">{{ $label }}</strong>
                    <span class="mt-1 block text-sm text-neutral-400">{{ $copy }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="section py-20">
        <p class="eyebrow">Dream Gym Private</p>
        <h2 class="mt-3 max-w-4xl text-4xl font-black md:text-5xl">{{ __('site.marketing_title') }}</h2>
        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach ([__('site.benefit_1'), __('site.benefit_2'), __('site.benefit_3')] as $index => $benefit)
                <div class="feature-card p-7">
                    <span class="text-3xl font-black text-[var(--brand-blue)]">0{{ $index + 1 }}</span>
                    <p class="mt-8 leading-7 text-neutral-300">{{ $benefit }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="border-y border-[var(--brand-stone)] bg-black/40 py-20">
        <div class="section">
            <p class="eyebrow">{{ __('site.how_it_works_title') }}</p>
            <h2 class="mt-3 text-4xl font-black">{{ app()->getLocale() === 'pt' ? 'Do registo ao treino em quatro passos.' : 'From sign-up to training in four steps.' }}</h2>
            <div class="mt-10 grid gap-4 md:grid-cols-4">
                @foreach ([
                    [__('site.how_it_works_account'), __('site.how_it_works_account_desc')],
                    [__('site.how_it_works_book'), __('site.how_it_works_book_desc')],
                    [__('site.how_it_works_access'), __('site.how_it_works_access_desc')],
                    [__('site.how_it_works_train'), __('site.how_it_works_train_desc')],
                ] as $index => [$step, $description])
                    <div class="feature-card p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full border-2 border-[var(--brand-blue)] text-lg font-black text-[var(--brand-blue)]">{{ $index + 1 }}</span>
                        <p class="mt-7 text-lg font-black uppercase">{{ $step }}</p>
                        <span class="mt-4 block h-1 w-12 bg-[var(--brand-blue)]"></span>
                        <p class="mt-4 text-sm leading-6 text-neutral-400">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="equipment" class="equipment-stage border-b border-[var(--brand-stone)] py-20">
        <div class="section">
            <p class="eyebrow">{{ __('site.equipment_eyebrow') }}</p>
            <h2 class="mt-3 max-w-2xl text-4xl font-black md:text-5xl">{{ __('site.equipment_title') }}</h2>
            <p class="mt-4 max-w-2xl text-neutral-300">{{ __('site.equipment_intro') }}</p>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ($equipmentGroups as $group)
                    <article class="equipment-card p-7 backdrop-blur-sm">
                        <h3 class="text-xl font-black uppercase text-[var(--brand-blue)]">{{ $group['title_'.app()->getLocale()] ?? $group['title_pt'] }}</h3>
                        <ul class="mt-6 space-y-3 text-sm text-neutral-200">
                            @foreach ($group['items'] ?? [] as $item)
                                <li class="flex gap-3"><span class="text-[var(--brand-blue)]">—</span><span>{{ $item['name_'.app()->getLocale()] ?? $item['name_pt'] }}</span></li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($personalTrainers->isNotEmpty())
        <section class="section py-20">
            <p class="eyebrow">Dream Team</p>
            <h2 class="mt-3 text-4xl font-black">{{ __('site.personal_trainers_title') }}</h2>
            <p class="mt-4 max-w-2xl text-neutral-400">{{ __('site.pt_section_intro') }}</p>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($personalTrainers as $trainer)
                    <details class="trainer-card p-6">
                        <summary class="flex items-center gap-4">
                            @if ($trainer->photo_url)
                                <img src="{{ $trainer->photo_url }}" alt="{{ $trainer->name }}" class="h-20 w-20 shrink-0 rounded-full object-cover ring-2 ring-[var(--brand-blue)]">
                            @else
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[var(--brand-blue)] text-xl font-black text-white">{{ $trainer->initials }}</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xl font-black">{{ $trainer->name }}</h3>
                                <p class="mt-1 text-sm font-bold text-[var(--brand-blue)]">{{ $trainer->localized('title') }}</p>
                                <span class="mt-2 block text-xs font-bold uppercase tracking-wider text-neutral-400">{{ __('site.pt_view_profile') }}</span>
                            </div>
                            <span class="trainer-more" aria-hidden="true"></span>
                        </summary>
                        <div class="mt-6 border-t border-[var(--brand-stone)] pt-5">
                            @if ($trainer->localized('specialties'))<p class="text-xs font-bold uppercase tracking-wide text-[var(--brand-blue)]">{{ $trainer->localized('specialties') }}</p>@endif
                            @if ($trainer->localized('bio'))<p class="mt-4 text-sm leading-7 text-neutral-300">{{ $trainer->localized('bio') }}</p>@endif
                            <div class="mt-5 flex flex-wrap gap-2 text-sm font-bold">
                                @if ($trainer->show_email && $trainer->email)<a class="btn-secondary" href="mailto:{{ $trainer->email }}">{{ __('site.pt_contact_email') }}</a>@endif
                                @if ($trainer->show_phone && $trainer->phone)<a class="btn-secondary" href="tel:{{ $trainer->phone }}">{{ __('site.pt_contact_phone') }}</a>@endif
                                @if ($trainer->show_whatsapp && $trainer->whatsapp_url)<a class="btn-primary" href="{{ $trainer->whatsapp_url }}" target="_blank" rel="noopener">{{ __('site.pt_contact_whatsapp') }}</a>@endif
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    <section id="faq" class="border-t border-[var(--brand-stone)] py-20">
        <div class="section">
            <p class="eyebrow">Suporte</p>
            <h2 class="mt-3 text-4xl font-black">{{ __('site.faq_title') }}</h2>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach (app(\App\Services\ProductCatalog::class)->faq() as $item)
                    <div class="feature-card p-6">
                        <h3 class="font-black">{{ $item['question_'.app()->getLocale()] ?? $item['question_pt'] ?? '' }}</h3>
                        <p class="mt-3 leading-7 text-neutral-400">{{ $item['answer_'.app()->getLocale()] ?? $item['answer_pt'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
