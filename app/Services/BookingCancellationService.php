<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingCancellationService
{
    public const CREDIT_REFUND_CUTOFF_HOURS = 24;

    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if ($booking->status === Booking::STATUS_CANCELLED) {
                return $booking->fresh(['user', 'room', 'accessCode']);
            }

            if ($this->shouldReturnCredit($booking)) {
                $booking->user?->increment('session_credits');
            }

            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            return $booking->fresh(['user', 'room', 'accessCode']);
        });
    }

    public function shouldReturnCredit(Booking $booking): bool
    {
        return $booking->user_id !== null
            && $booking->booking_type === Booking::TYPE_SINGLE_HOUR
            && $booking->payment_status === 'paid'
            && in_array($booking->paid_with, [Booking::PAID_WITH_CREDITS, Booking::PAID_WITH_PAYMENT], true)
            && $booking->starts_at->greaterThanOrEqualTo(now()->addHours(self::CREDIT_REFUND_CUTOFF_HOURS));
    }
}
