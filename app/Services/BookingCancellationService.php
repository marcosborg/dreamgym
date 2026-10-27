<?php

namespace App\Services;

use App\Mail\BookingCancelled;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingCancellationService
{
    public const CREDIT_REFUND_CUTOFF_HOURS = 12;

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
                $user = User::lockForUpdate()->find($booking->user_id);
                if ($user) {
                    $kind = $booking->paid_with === Booking::PAID_WITH_MEMBERSHIP ? 'membership' : 'session';
                    $before = $user->{$kind.'_credits'};
                    if ($booking->paid_with === Booking::PAID_WITH_MEMBERSHIP) {
                        $user->increment('membership_credits');
                    } else {
                        app(SessionCreditService::class)->refund($user, $booking);
                    }
                    CreditHistory::record($user, $kind, $user->fresh()->{$kind.'_credits'} - $before, 'cancellation', $booking->id);
                }
            }

            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            if (! $booking->suppress_notifications) {
                DB::afterCommit(function () use ($booking) {
                    try {
                        Mail::to($booking->customer_email)->bcc('info@dreamgym.pt')->send(new BookingCancelled($booking->fresh()));
                    } catch (\Throwable $e) {
                        Log::error('Falha no email de cancelamento.', ['booking_id' => $booking->id]);
                    }
                });
            }

            return $booking->fresh(['user', 'room', 'accessCode']);
        });
    }

    public function shouldReturnCredit(Booking $booking): bool
    {
        return $booking->user_id !== null
            && $booking->booking_type === Booking::TYPE_SINGLE_HOUR
            && $booking->payment_status === 'paid'
            && in_array($booking->paid_with, [Booking::PAID_WITH_CREDITS, Booking::PAID_WITH_MEMBERSHIP, Booking::PAID_WITH_PAYMENT], true)
            && $booking->starts_at->greaterThanOrEqualTo(now()->addHours(self::CREDIT_REFUND_CUTOFF_HOURS));
    }
}
