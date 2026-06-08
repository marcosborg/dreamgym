<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Setting;

class ProductCatalog
{
    public const SESSION_PACK = 'session_pack';

    public const MEMBERSHIP = 'membership';

    public const GROUP_HOUR = 'group_hour';

    public const SINGLE_HOUR = 'single_hour';

    public const SESSION_PACK_CREDITS = 10;

    public const MEMBERSHIP_DAYS = 30;

    public function sessionPack(Room $room): array
    {
        return [
            'active' => (bool) Setting::getValue('product_session_pack_active', true),
            'name' => Setting::getValue('product_session_pack_name', 'Pack 10 sessões'),
            'price_cents' => (int) (Setting::getValue('product_session_pack_price_cents')
                ?? round($room->slot_price_cents * self::SESSION_PACK_CREDITS * 0.9)),
            'credits' => self::SESSION_PACK_CREDITS,
        ];
    }

    public function membership(Room $room): array
    {
        return [
            'active' => (bool) Setting::getValue('product_membership_active', false),
            'name' => Setting::getValue('product_membership_name', 'Mensalidade'),
            'price_cents' => (int) (Setting::getValue('product_membership_price_cents') ?? ($room->slot_price_cents * 12)),
            'days' => self::MEMBERSHIP_DAYS,
        ];
    }

    public function groupHour(Room $room): array
    {
        return [
            'active' => (bool) Setting::getValue('product_group_hour_active', true),
            'name' => Setting::getValue('product_group_hour_name', 'Grupo privado'),
            'price_cents' => (int) (Setting::getValue('product_group_hour_price_cents')
                ?? round($room->slot_price_cents * $room->capacity * 0.85)),
            'seats' => $room->capacity,
        ];
    }

    public function faq(): array
    {
        return Setting::getValue('faq_items', [
            [
                'question_pt' => __('site.faq_1_q', locale: 'pt'),
                'answer_pt' => __('site.faq_1_a', locale: 'pt'),
                'question_en' => __('site.faq_1_q', locale: 'en'),
                'answer_en' => __('site.faq_1_a', locale: 'en'),
            ],
            [
                'question_pt' => __('site.faq_2_q', locale: 'pt'),
                'answer_pt' => __('site.faq_2_a', locale: 'pt'),
                'question_en' => __('site.faq_2_q', locale: 'en'),
                'answer_en' => __('site.faq_2_a', locale: 'en'),
            ],
        ]);
    }

    public function formattedPrice(int $priceCents, string $currency = 'EUR'): string
    {
        return number_format($priceCents / 100, 2, ',', ' ') . ' ' . $currency;
    }
}
