<?php

namespace App\Services;

use App\Models\LegalTermSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LegalTerms
{
    /**
     * @return Collection<int, array{title: string, body: string}>
     */
    public function sections(string $documentType = LegalTermSection::DOCUMENT_TERMS, ?string $locale = null): Collection
    {
        $locale = ($locale ?? app()->getLocale()) === 'en' ? 'en' : 'pt';

        if (Schema::hasTable('legal_term_sections')) {
            $sections = LegalTermSection::query()
                ->forDocument($documentType)
                ->active()
                ->ordered()
                ->get()
                ->map(fn (LegalTermSection $section): array => [
                    'title' => $section->localizedTitle($locale),
                    'body' => $this->sanitizeBody($section->localizedBody($locale)),
                ]);

            if ($sections->isNotEmpty()) {
                return $sections;
            }
        }

        if ($documentType === LegalTermSection::DOCUMENT_PRIVACY) {
            return collect([
                ['title' => __('site.privacy_data_title', locale: $locale), 'body' => $this->plainTextBody(__('site.privacy_data_body', locale: $locale))],
                ['title' => __('site.privacy_use_title', locale: $locale), 'body' => $this->plainTextBody(__('site.privacy_use_body', locale: $locale))],
                ['title' => __('site.privacy_storage_title', locale: $locale), 'body' => $this->plainTextBody(__('site.privacy_storage_body', locale: $locale))],
                ['title' => __('site.privacy_rights_title', locale: $locale), 'body' => $this->plainTextBody(__('site.privacy_rights_body', locale: $locale))],
                ['title' => __('site.privacy_contact_title', locale: $locale), 'body' => $this->plainTextBody(__('site.privacy_contact_body', locale: $locale))],
            ]);
        }

        return collect([
            ['title' => __('site.terms_booking_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_booking_body', locale: $locale))],
            ['title' => __('site.terms_access_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_access_body', locale: $locale))],
            ['title' => __('site.terms_payment_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_payment_body', locale: $locale))],
            ['title' => __('site.terms_cancellation_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_cancellation_body', locale: $locale))],
            ['title' => __('site.terms_children_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_children_body', locale: $locale))],
            ['title' => __('site.terms_use_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_use_body', locale: $locale))],
            ['title' => __('site.terms_contact_title', locale: $locale), 'body' => $this->plainTextBody(__('site.terms_contact_body', locale: $locale))],
        ]);
    }

    private function sanitizeBody(string $body): string
    {
        if (! Str::contains($body, ['<p', '<ul', '<ol', '<strong', '<b', '<em', '<i', '<h2', '<h3', '<blockquote'])) {
            return $this->plainTextBody($body);
        }

        $body = strip_tags($body, '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><h2><h3>');
        $body = preg_replace_callback('/<([a-z0-9]+)(\s[^>]*)?>/i', function (array $matches): string {
            $tag = strtolower($matches[1]);
            $attributes = $matches[2] ?? '';

            if ($tag !== 'a') {
                return "<{$tag}>";
            }

            if (! preg_match('/\shref\s*=\s*([\'"])(.*?)\1/i', $attributes, $hrefMatch)) {
                return '<a>';
            }

            $href = trim($hrefMatch[2]);

            if (! preg_match('/^(https?:|mailto:|tel:|\/|#)/i', $href)) {
                return '<a>';
            }

            return '<a href="'.e($href).'">';
        }, $body) ?? '';

        return trim($body);
    }

    private function plainTextBody(string $body): string
    {
        return '<p>'.nl2br(e($body), false).'</p>';
    }
}
