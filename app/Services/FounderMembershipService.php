<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class FounderMembershipService
{
    public const PRICE_CENTS = 4000;

    public const GRACE_DAYS = 3;

    public const CAMPAIGN_END = '2026-12-31 23:59:59';

    /** Replay paid renewals so paying again after a break never restores the discount. */
    public function eligible(User $user): bool
    {
        $expires = null;
        $payments = $user->payments()->whereNull('booking_id')->where('product_type', ProductCatalog::MEMBERSHIP)
            ->where('status', 'paid')->orderByRaw('COALESCE(paid_at, created_at)')->orderBy('id')->get();
        $joinedAt = $payments->first()?->paid_at ?? $payments->first()?->created_at ?? now();
        if (! $user->is_founder && $joinedAt->greaterThan(Carbon::parse(self::CAMPAIGN_END))) {
            return false;
        }
        foreach ($payments as $payment) {
            $paidAt = $payment->paid_at ?? $payment->created_at;
            if ($expires && $paidAt->greaterThan($expires->copy()->addDays(self::GRACE_DAYS))) {
                return false;
            }
            $start = $paidAt->copy()->startOfDay();
            $expires = $start->copy()->addDays(max(1, (int) ($payment->metadata['days'] ?? ProductCatalog::MEMBERSHIP_DAYS)));
        }

        // A newly designated founder can make their first purchase at the founder price.
        return ! $expires || now()->lessThanOrEqualTo($expires->copy()->addDays(self::GRACE_DAYS));
    }

    public function priceFor(array $product, ?User $user): array
    {
        if ($user && $product['type'] === ProductCatalog::MEMBERSHIP
            && ($product['currency'] ?? 'EUR') === 'EUR'
            && (int) ($product['days'] ?? ProductCatalog::MEMBERSHIP_DAYS) === 30
            && $this->eligible($user)) {
            $product['price_cents'] = min($product['price_cents'], self::PRICE_CENTS);
            $product['founder_price'] = true;
        }

        return $product;
    }
}
