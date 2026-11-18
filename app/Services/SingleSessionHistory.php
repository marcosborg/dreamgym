<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CreditMovement;
use Illuminate\Support\Facades\DB;

class SingleSessionHistory
{
    public function record(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = Booking::lockForUpdate()->findOrFail($booking->id);
            if (! $booking->user_id || $booking->duplicate_of_id
                || $booking->booking_type !== Booking::TYPE_SINGLE_HOUR
                || $booking->paid_with !== Booking::PAID_WITH_PAYMENT
                || $booking->payment_status !== 'paid' || $booking->price_cents <= 0
                || ! $booking->confirmed_at) {
                return;
            }

            // A directly purchased session is consumed by this booking, not added to the pack wallet.
            foreach (['single_purchase' => [1, 1], 'booking' => [-1, 0]] as $reason => [$amount, $balance]) {
                CreditMovement::firstOrCreate([
                    'booking_id' => $booking->id, 'kind' => 'single_session', 'reason' => $reason,
                ], [
                    'user_id' => $booking->user_id, 'amount' => $amount, 'balance' => $balance,
                    'actor_id' => null, 'created_at' => $booking->confirmed_at, 'updated_at' => $booking->confirmed_at,
                ]);
            }
        });
    }
}
