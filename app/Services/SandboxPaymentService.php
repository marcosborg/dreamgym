<?php

namespace App\Services;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Locks\LockProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                'terms_accepted_at' => $booking->terms_accepted_at,
                'metadata' => ['label' => 'Multibanco / MB Way sandbox placeholder'],
            ]
        );
    }

    public function complete(Payment $payment): Booking
    {
        return DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $booking = $payment->booking()->lockForUpdate()->firstOrFail();
            $booking->update([
                'status' => Booking::STATUS_CONFIRMED,
                'payment_status' => 'paid',
                'payment_reference' => $payment->reference,
                'terms_accepted_at' => $booking->terms_accepted_at ?? $payment->terms_accepted_at,
                'confirmed_at' => now(),
            ]);

            $accessCode = app(AccessCodeService::class)->createForBooking($booking);
            if (config('lock.provider') === 'ttlock') {
                // Physical access and rotated tokens must survive independently of the booking transaction.
                DB::afterCommit(fn () => $this->activateTtlockAccess($booking->id));
            } else {
                app(LockProvisioningService::class)->provision($accessCode);
                Mail::to($booking->customer_email)->send(new BookingConfirmed($booking->fresh(['room', 'accessCode'])));
                if ($accessCode->fresh()->ready_for_use) {
                    $accessCode->update(['access_notified_at' => now()]);
                }
            }

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
            if (config('lock.provider') === 'ttlock') {
                // Physical access and rotated tokens must survive independently of the booking transaction.
                DB::afterCommit(fn () => $this->activateTtlockAccess($booking->id));
            } else {
                app(LockProvisioningService::class)->provision($accessCode);
                Mail::to($booking->customer_email)->send(new BookingConfirmed($booking->fresh(['room', 'accessCode'])));
                if ($accessCode->fresh()->ready_for_use) {
                    $accessCode->update(['access_notified_at' => now()]);
                }
            }

            return $booking->fresh(['payment', 'accessCode', 'room']);
        });
    }

    private function activateTtlockAccess(int $bookingId): void
    {
        $booking = Booking::with(['accessCode', 'room'])->findOrFail($bookingId);
        if ($booking->status !== Booking::STATUS_CONFIRMED || $booking->payment_status !== 'paid') {
            return;
        }
        $code = app(LockProvisioningService::class)->provision($booking->accessCode);
        try {
            Mail::to($booking->customer_email)->send(new BookingConfirmed($booking->fresh(['room', 'accessCode'])));
            if ($code->ready_for_use) {
                $code->update(['access_notified_at' => now()]);
            }
        } catch (\Throwable) {
            // ttlock:sync retries delivery; never roll back a paid booking or an active lock PIN.
            Log::warning('Email de acesso TTLock pendente.', ['booking_id' => $bookingId]);
        }
    }

    public function completePurchase(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === 'paid') {
                return $payment->fresh('user');
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $user = $payment->user()->lockForUpdate()->firstOrFail();

            if ($payment->product_type === ProductCatalog::SESSION_PACK) {
                $user->increment(
                    'session_credits',
                    (int) ($payment->metadata['credits'] ?? ProductCatalog::SESSION_PACK_CREDITS),
                );
            }

            if ($payment->product_type === ProductCatalog::MEMBERSHIP) {
                app(MembershipReconciliationService::class)->reconcile($user);
            }

            return $payment->fresh('user');
        });
    }
}
