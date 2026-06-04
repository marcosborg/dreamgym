<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\SandboxPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Booking $booking, SandboxPaymentService $payments): View
    {
        $booking->load(['room', 'payment']);
        $payment = $booking->payment ?: $payments->createPayment($booking);

        return view('checkout.show', compact('booking', 'payment'));
    }

    public function complete(Booking $booking, SandboxPaymentService $payments): RedirectResponse
    {
        abort_if($booking->status === Booking::STATUS_CANCELLED, 422);

        $payment = $booking->payment ?: $payments->createPayment($booking);
        $booking = $payments->complete($payment);

        return redirect()->route('booking.confirmed', $booking);
    }

    public function confirmed(Booking $booking): View
    {
        $booking->load(['room', 'payment', 'accessCode']);

        return view('checkout.confirmed', compact('booking'));
    }
}
