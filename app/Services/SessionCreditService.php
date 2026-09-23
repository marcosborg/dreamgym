<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\SessionCreditLot;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SessionCreditService
{
    public const VALIDITY_DAYS = 90;

    // Mutations run inside the caller's transaction with the user row locked.
    public function grantPurchase(User $user, Payment $payment): void
    {
        $credits = max(0, (int) ($payment->metadata['credits'] ?? ProductCatalog::SESSION_PACK_CREDITS));
        $lot = SessionCreditLot::firstOrCreate(['payment_id' => $payment->id], [
            'user_id' => $user->id, 'credits_granted' => $credits, 'remaining_credits' => $credits,
            'expires_at' => ($payment->paid_at ?? now())->copy()->addDays(self::VALIDITY_DAYS),
        ]);
        if ($lot->wasRecentlyCreated) {
            $user->increment('session_credits', $credits);
        }
    }

    public function expire(User $user): void
    {
        $lots = SessionCreditLot::where('user_id', $user->id)->where('remaining_credits', '>', 0)
            ->orderBy('expires_at')->orderBy('id')->lockForUpdate()->get();
        // Respect manual downward adjustments made in the backoffice.
        $excess = max(0, $lots->sum('remaining_credits') - $user->session_credits);
        foreach ($lots as $lot) {
            $removed = min($excess, $lot->remaining_credits);
            if ($removed) {
                $lot->decrement('remaining_credits', $removed);
                $excess -= $removed;
            }
            if ($lot->expires_at->lessThanOrEqualTo(now()) && $lot->remaining_credits > 0) {
                $user->decrement('session_credits', $lot->remaining_credits);
                $lot->update(['remaining_credits' => 0]);
            }
        }
    }

    public function availableFor(User $user, CarbonInterface $startsAt): int
    {
        $this->expire($user);
        $lots = SessionCreditLot::where('user_id', $user->id)->where('remaining_credits', '>', 0)->get();
        $undated = max(0, $user->session_credits - $lots->sum('remaining_credits'));

        return $undated + $lots->filter(fn ($lot) => $lot->expires_at->greaterThan($startsAt))->sum('remaining_credits');
    }

    public function consume(User $user, CarbonInterface $startsAt): ?int
    {
        if ($this->availableFor($user, $startsAt) < 1) {
            throw ValidationException::withMessages(['starts_at' => __('site.no_valid_session_credits')]);
        }
        $lot = SessionCreditLot::where('user_id', $user->id)->where('remaining_credits', '>', 0)
            ->where('expires_at', '>', $startsAt)->orderBy('expires_at')->orderBy('id')->lockForUpdate()->first();
        $lot?->decrement('remaining_credits');
        $user->decrement('session_credits');

        return $lot?->id;
    }

    public function refund(User $user, Booking $booking): void
    {
        if ($booking->session_credit_lot_id) {
            $lot = SessionCreditLot::lockForUpdate()->findOrFail($booking->session_credit_lot_id);
            if ($lot->expires_at->isFuture()) {
                $lot->increment('remaining_credits');
                $user->increment('session_credits');
            }

            return;
        }
        if ($booking->paid_with === Booking::PAID_WITH_PAYMENT) {
            $payment = $booking->payment;
            $expires = ($payment?->paid_at ?? $booking->confirmed_at ?? $booking->created_at)->copy()->addDays(self::VALIDITY_DAYS);
            if ($expires->isFuture()) {
                $lot = SessionCreditLot::firstOrCreate(['source_booking_id' => $booking->id], [
                    'user_id' => $user->id, 'credits_granted' => 1, 'remaining_credits' => 1, 'expires_at' => $expires,
                ]);
                if ($lot->wasRecentlyCreated) {
                    $user->increment('session_credits');
                }
            }

            return;
        }
        // Legacy/manual credits have no provable purchase date; preserve them without inventing one.
        $user->increment('session_credits');
    }

    public function summary(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $this->expire($locked);
            $lots = SessionCreditLot::where('user_id', $user->id)->where('remaining_credits', '>', 0)
                ->orderBy('expires_at')->orderBy('id')->get();
            $user->refresh();

            return [
                'dates' => $lots->map(fn ($lot) => ['credits' => $lot->remaining_credits, 'expires_at' => $lot->expires_at])->all(),
                'undated' => max(0, $locked->session_credits - $lots->sum('remaining_credits')),
            ];
        });
    }
}
