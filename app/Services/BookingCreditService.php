<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingCreditService
{
    public function coverAdminBooking(Booking $booking): void
    {
        if ($booking->booking_type !== Booking::TYPE_SINGLE_HOUR || $booking->status !== Booking::STATUS_CONFIRMED) {
            return;
        }
        DB::transaction(function () use ($booking) {
            $user = $booking->user_id
                ? User::query()->lockForUpdate()->find($booking->user_id)
                : User::query()->where('email', $booking->customer_email)->lockForUpdate()->first();
            if (! $user) {
                return;
            }
            $booking->updateQuietly(['user_id' => $user->id]);
            // Explicitly paid cash/card reservations must not consume a second payment.
            if ($booking->paid_with === Booking::PAID_WITH_PAYMENT && $booking->price_cents > 0) {
                return;
            }
            $creditLotId = null;
            $credits = app(SessionCreditService::class);
            if ($user->hasActiveMembership() && $booking->starts_at->lessThan($user->membership_expires_at)) {
                $user->decrement('membership_credits');
                $method = Booking::PAID_WITH_MEMBERSHIP;
            } elseif ($credits->availableFor($user, $booking->starts_at) > 0) {
                $creditLotId = $credits->consume($user, $booking->starts_at);
                $method = Booking::PAID_WITH_CREDITS;
            } else {
                if (in_array($booking->paid_with, [Booking::PAID_WITH_MEMBERSHIP, Booking::PAID_WITH_CREDITS], true)) {
                    throw ValidationException::withMessages(['data.paid_with' => 'O cliente não tem créditos válidos para esta reserva.']);
                }

                return;
            }
            $booking->updateQuietly(['session_credit_lot_id' => $creditLotId, 'paid_with' => $method, 'price_cents' => 0, 'payment_status' => 'paid']);
        });
    }
}
