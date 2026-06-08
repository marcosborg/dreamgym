<?php

namespace App\Services;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ProductCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SandboxPaymentService
{
    public function createPayment(Booking $booking): Payment
    {
        return Payment::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'provider' => 'sandbox_mbway_placeholder',
                'reference' => 'DG-'.Str::upper(Str::random(10)),
                'product_type' => $booking->booking_type,
                'amount_cents' => $booking->price_cents,
                'currency' => $booking->currency,
                'status' => 'pending',
                'metadata' => ['label' => 'Multibanco / MB Way sandbox placeholder'],
            ]
        );
    }

    public function complete(Payment $payment): Booking
    {
        return DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $booking = $payment->booking()->lockForUpdate()->firstOrFail();
            $booking->update([
                'status' => Booking::STATUS_CONFIRMED,
                'payment_status' => 'paid',
                'payment_reference' => $payment->reference,
                'confirmed_at' => now(),
            ]);

            $accessCode = app(AccessCodeService::class)->createForBooking($booking);
            app(SimulatedLockService::class)->provision($accessCode);

            Mail::to($booking->customer_email)->send(new BookingConfirmed($booking->fresh(['room', 'accessCode'])));

            return $booking->fresh(['payment', 'accessCode', 'room']);
        });
    }

    public function confirmCoveredBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => Booking::STATUS_CONFIRMED,
                'payment_status' => 'paid',
                'confirmed_at' => now(),
            ]);

            $accessCode = app(AccessCodeService::class)->createForBooking($booking);
            app(SimulatedLockService::class)->provision($accessCode);

            Mail::to($booking->customer_email)->send(new BookingConfirmed($booking->fresh(['room', 'accessCode'])));

            return $booking->fresh(['payment', 'accessCode', 'room']);
        });
    }

    public function completePurchase(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status === 'paid') {
                return $payment->fresh('user');
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $user = $payment->user()->lockForUpdate()->firstOrFail();

            if ($payment->product_type === ProductCatalog::SESSION_PACK) {
                $user->increment('session_credits', ProductCatalog::SESSION_PACK_CREDITS);
            }

            if ($payment->product_type === ProductCatalog::MEMBERSHIP) {
                $startsAt = $user->membership_expires_at?->isFuture()
                    ? $user->membership_expires_at
                    : now();

                $user->update([
                    'membership_expires_at' => $startsAt->copy()->addDays(ProductCatalog::MEMBERSHIP_DAYS),
                ]);
            }

            return $payment->fresh('user');
        });
    }
}
