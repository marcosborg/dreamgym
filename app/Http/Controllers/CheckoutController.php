<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\SandboxPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Booking $booking, SandboxPaymentService $payments): View
    {
        $booking->load(['room', 'payment']);
        $payment = $booking->payment ?: $payments->createPayment($booking);

        return view('checkout.show', compact('booking', 'payment'));
    }

    public function complete(Booking $booking, Request $request, SandboxPaymentService $payments): RedirectResponse
    {
        $request->validate([
            'terms_accepted' => ['accepted'],
        ]);

        abort_if($booking->status === Booking::STATUS_CANCELLED, 422);

        $booking->update([
            'terms_accepted_at' => $booking->terms_accepted_at ?? now(),
        ]);

        $payment = $booking->payment ?: $payments->createPayment($booking);
        $payment->update([
            'terms_accepted_at' => $payment->terms_accepted_at ?? now(),
        ]);
        $booking = $payments->complete($payment);

        return redirect()->route('booking.confirmed', $booking);
    }

    public function confirmed(Booking $booking): View
    {
        $booking->load(['room', 'payment', 'accessCode']);

        return view('checkout.confirmed', compact('booking'));
    }
}
