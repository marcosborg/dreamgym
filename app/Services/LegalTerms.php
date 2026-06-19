<?php

namespace App\Services;

use App\Models\LegalTermSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LegalTerms
{
    /**
     * @return Collection<int, array{title: string, body: string}>
     */
    public function sections(?string $locale = null): Collection
    {
        $locale = ($locale ?? app()->getLocale()) === 'en' ? 'en' : 'pt';

        if (Schema::hasTable('legal_term_sections')) {
            $sections = LegalTermSection::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (LegalTermSection $section): array => [
                    'title' => $section->localizedTitle($locale),
                    'body' => $section->localizedBody($locale),
                ]);

            if ($sections->isNotEmpty()) {
                return $sections;
            }
        }

        return collect([
            ['title' => __('site.terms_booking_title', locale: $locale), 'body' => __('site.terms_booking_body', locale: $locale)],
            ['title' => __('site.terms_access_title', locale: $locale), 'body' => __('site.terms_access_body', locale: $locale)],
            ['title' => __('site.terms_payment_title', locale: $locale), 'body' => __('site.terms_payment_body', locale: $locale)],
            ['title' => __('site.terms_cancellation_title', locale: $locale), 'body' => __('site.terms_cancellation_body', locale: $locale)],
            ['title' => __('site.terms_children_title', locale: $locale), 'body' => __('site.terms_children_body', locale: $locale)],
            ['title' => __('site.terms_use_title', locale: $locale), 'body' => __('site.terms_use_body', locale: $locale)],
            ['title' => __('site.terms_contact_title', locale: $locale), 'body' => __('site.terms_contact_body', locale: $locale)],
        ]);
    }
}
