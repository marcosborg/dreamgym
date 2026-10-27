<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CreditMovement;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MembershipReconciliationService
{
    /** Rebuild from dated payments and reservations; replaying never grants credits twice. */
    public function reconcile(User $user, bool $includeLegacyBookings = false, bool $persist = true, int $refundCutoffHours = BookingCancellationService::CREDIT_REFUND_CUTOFF_HOURS): array
    {
        return DB::transaction(function () use ($user, $includeLegacyBookings, $persist, $refundCutoffHours) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $payments = Payment::query()->where('user_id', $user->id)
                ->whereNull('booking_id')->where('product_type', ProductCatalog::MEMBERSHIP)
                ->where('status', 'paid')->orderBy('paid_at')->orderBy('id')->get();

            if ($payments->isEmpty()) {
                return ['user_id' => $user->id, 'payments' => 0, 'bookings' => 0, 'credits' => $user->membership_credits, 'uncovered' => 0];
            }

            $events = [];
            foreach ($payments as $payment) {
                $events[] = ['at' => ($payment->paid_at ?? $payment->created_at)->copy()->startOfDay(), 'type' => 0, 'record' => $payment];
            }

            $bookings = Booking::query()->where(function ($query) use ($user, $includeLegacyBookings) {
                $query->where('user_id', $user->id);
                if ($includeLegacyBookings) {
                    $query->orWhere(fn ($q) => $q->whereNull('user_id')->where('customer_email', $user->email));
                }
            })->whereNull('duplicate_of_id')->where('booking_type', Booking::TYPE_SINGLE_HOUR)
                ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_CANCELLED])
                ->where(function ($q) use ($includeLegacyBookings) {
                    $q->where('payment_status', 'paid');
                    if ($includeLegacyBookings) {
                        $q->orWhere(fn ($p) => $p->where('status', Booking::STATUS_CONFIRMED)->where('paid_with', Booking::PAID_WITH_MEMBERSHIP));
                    }
                })
                ->where(function ($query) use ($includeLegacyBookings) {
                    $query->where('paid_with', Booking::PAID_WITH_MEMBERSHIP);
                    if ($includeLegacyBookings) {
                        $query->orWhere(fn ($q) => $q->where(fn ($p) => $p->whereNull('paid_with')->orWhere(fn ($z) => $z->where('paid_with', Booking::PAID_WITH_PAYMENT)->where('price_cents', 0)))
                            ->whereDoesntHave('payment', fn ($p) => $p->where('status', 'paid')->where('amount_cents', '>', 0)));
                    }
                })->get();

            foreach ($bookings as $booking) {
                $events[] = ['at' => $booking->confirmed_at ?? $booking->created_at, 'type' => 1, 'record' => $booking];
                if ($booking->status === Booking::STATUS_CANCELLED && $booking->cancelled_at
                    && $booking->starts_at->greaterThanOrEqualTo($booking->cancelled_at->copy()->addHours($refundCutoffHours))) {
                    $events[] = ['at' => $booking->cancelled_at, 'type' => 2, 'record' => $booking];
                }
            }
            foreach (CreditMovement::where('user_id', $user->id)->where('kind', 'membership')->where('reason', 'like', 'manual:%')->get() as $movement) {
                $events[] = ['at' => $movement->created_at, 'type' => 3, 'record' => $movement];
            }
            usort($events, fn ($a, $b) => ($a['at'] <=> $b['at']) ?: ($a['type'] <=> $b['type']) ?: ($a['record']->id <=> $b['record']->id));

            $credits = 0;
            $cycle = 0;
            $expires = null;
            $cycleStarts = null;
            $charged = [];
            $uncovered = 0;
            foreach ($events as $event) {
                $record = $event['record'];
                if ($event['type'] === 0) {
                    if (! $expires || $expires->lessThanOrEqualTo($event['at'])) {
                        $credits = 0;
                        $cycle++;
                        $expires = $event['at']->copy();
                        $cycleStarts = $event['at']->copy();
                    }
                    $credits += max(0, (int) ($record->metadata['credits'] ?? ProductCatalog::MEMBERSHIP_CREDITS));
                    $expires = $expires->copy()->addDays(max(1, (int) ($record->metadata['days'] ?? ProductCatalog::MEMBERSHIP_DAYS)));
                } elseif ($event['type'] === 3) {
                    if ($expires && $event['at']->lessThan($expires)) {
                        $credits = max(0, $credits + $record->amount);
                    }
                } elseif ($event['type'] === 2) {
                    if (isset($charged[$record->id]) && $cycle === $charged[$record->id]) {
                        $credits++;
                    }
                } elseif ($expires && $event['at']->lessThan($expires) && $record->starts_at->greaterThanOrEqualTo($cycleStarts) && $record->starts_at->lessThan($expires) && $credits > 0) {
                    $credits--;
                    $charged[$record->id] = $cycle;
                    if ($includeLegacyBookings && $persist) {
                        $record->updateQuietly(['user_id' => $user->id, 'paid_with' => Booking::PAID_WITH_MEMBERSHIP, 'payment_status' => 'paid']);
                    }
                } else {
                    $uncovered++;
                }
            }
            $delta = $credits - $user->membership_credits;
            if ($persist) {
                $user->update(['membership_credits' => $credits, 'membership_expires_at' => $expires]);
                CreditHistory::record($user, 'membership', $delta, 'membership_recalculation');
            }

            return ['user_id' => $user->id, 'payments' => $payments->count(), 'bookings' => count($charged), 'credits' => $credits, 'uncovered' => $uncovered];
        });
    }
}
