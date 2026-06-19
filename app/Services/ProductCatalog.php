<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProductCatalog
{
    public const SESSION_PACK = Product::TYPE_SESSION_PACK;

    public const MEMBERSHIP = Product::TYPE_MEMBERSHIP;

    public const GROUP_HOUR = Product::TYPE_GROUP_HOUR;

    public const SINGLE_HOUR = Product::TYPE_SINGLE_HOUR;

    public const SESSION_PACK_CREDITS = 10;

    public const MEMBERSHIP_DAYS = 30;

    public function singleHour(Room $room): array
    {
        return $this->firstOfType(self::SINGLE_HOUR, $room, [
            'name' => 'Hora individual',
            'price_cents' => $room->slot_price_cents,
            'currency' => $room->currency,
            'seats' => 1,
        ]);
    }

    public function sessionPack(Room $room): array
    {
        return $this->firstOfType(self::SESSION_PACK, $room, [
            'name' => 'Pack 10 sessões',
            'price_cents' => (int) round($room->slot_price_cents * self::SESSION_PACK_CREDITS * 0.9),
            'currency' => $room->currency,
            'credits' => self::SESSION_PACK_CREDITS,
        ]);
    }

    public function membership(Room $room): array
    {
        return $this->firstOfType(self::MEMBERSHIP, $room, [
            'active' => false,
            'name' => 'Mensalidade',
            'price_cents' => $room->slot_price_cents * 12,
            'currency' => $room->currency,
            'days' => self::MEMBERSHIP_DAYS,
        ]);
    }

    public function groupHour(Room $room): array
    {
        return $this->firstOfType(self::GROUP_HOUR, $room, [
            'name' => 'Grupo privado',
            'price_cents' => (int) round($room->slot_price_cents * $room->capacity * 0.85),
            'currency' => $room->currency,
            'seats' => $room->capacity,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function purchaseProducts(Room $room): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect([$this->sessionPack($room), $this->membership($room)])
                ->filter(fn (array $product): bool => (bool) $product['active'])
                ->values();
        }

        return Product::query()
            ->active()
            ->whereIn('type', [self::SESSION_PACK, self::MEMBERSHIP])
            ->ordered()
            ->get()
            ->map(fn (Product $product): array => $this->toArray($product, $room));
    }

    public function findPurchaseProduct(int $id, Room $room): ?array
    {
        if (! Schema::hasTable('products')) {
            return null;
        }

        $product = Product::query()
            ->active()
            ->whereIn('type', [self::SESSION_PACK, self::MEMBERSHIP])
            ->find($id);

        return $product ? $this->toArray($product, $room) : null;
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

    /**
     * @param array<string, mixed> $fallback
     * @return array<string, mixed>
     */
    private function firstOfType(string $type, Room $room, array $fallback): array
    {
        if (Schema::hasTable('products')) {
            $product = Product::query()
                ->where('type', $type)
                ->ordered()
                ->first();

            if ($product) {
                return $this->toArray($product, $room);
            }
        }

        return [
            'id' => null,
            'type' => $type,
            'active' => $fallback['active'] ?? true,
            'name' => $fallback['name'],
            'price_cents' => $fallback['price_cents'],
            'currency' => $fallback['currency'] ?? $room->currency,
            'credits' => $fallback['credits'] ?? null,
            'days' => $fallback['days'] ?? null,
            'seats' => $fallback['seats'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Product $product, Room $room): array
    {
        return [
            'id' => $product->id,
            'type' => $product->type,
            'active' => $product->is_active,
            'name' => $product->name,
            'price_cents' => $product->price_cents,
            'currency' => $product->currency ?: $room->currency,
            'credits' => $product->credits,
            'days' => $product->days,
            'seats' => $product->seats,
        ];
    }
}
