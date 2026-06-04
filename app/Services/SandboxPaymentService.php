<?php

namespace App\Services;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
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
}
