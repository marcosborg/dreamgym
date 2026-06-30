<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\Payments\IfthenpayPaymentService;
use App\Services\Payments\PaymentProvider;
use App\Services\SandboxPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Booking $booking, SandboxPaymentService $payments, IfthenpayPaymentService $ifthenpay, PaymentProvider $provider): View
    {
        $booking->load(['room', 'payment']);
        $payment = $booking->payment ?: ($provider->isIfthenpay()
            ? $ifthenpay->createPayment($booking)
            : $payments->createPayment($booking));

        return view('checkout.show', [
            'booking' => $booking,
            'payment' => $payment,
            'paymentProvider' => $provider->isIfthenpay() ? 'ifthenpay' : 'sandbox',
        ]);
    }

    public function complete(Booking $booking, Request $request, SandboxPaymentService $payments, IfthenpayPaymentService $ifthenpay, PaymentProvider $provider): RedirectResponse
    {
        $rules = [
            'terms_accepted' => ['accepted'],
        ];

        if ($provider->isIfthenpay()) {
            $rules['payment_method'] = ['required', 'in:multibanco,mbway'];
            $rules['mbway_phone'] = ['required_if:payment_method,mbway', 'nullable', 'string', 'max:30'];
        }

        $data = $request->validate($rules);

        abort_if($booking->status === Booking::STATUS_CANCELLED, 422);

        $booking->update([
            'terms_accepted_at' => $booking->terms_accepted_at ?? now(),
        ]);

        $payment = $booking->payment ?: ($provider->isIfthenpay()
            ? $ifthenpay->createPayment($booking)
            : $payments->createPayment($booking));
        $payment->update([
            'terms_accepted_at' => $payment->terms_accepted_at ?? now(),
        ]);

        if ($provider->isIfthenpay()) {
            try {
                $ifthenpay->initialize($payment, $data['payment_method'], $data['mbway_phone'] ?? null);
            } catch (\Throwable $exception) {
                $ifthenpay->markInitializationFailure($payment, $exception);

                return back()->withErrors([
                    'payment_method' => __('site.payment_initialization_failed'),
                ])->withInput();
            }

            return redirect()->route('checkout.show', $booking)
                ->with('status', __('site.payment_pending_confirmation'));
        }

        $booking = $payments->complete($payment);

        return redirect()->route('booking.confirmed', $booking);
    }

    public function confirmed(Booking $booking): View
    {
        $booking->load(['room', 'payment', 'accessCode']);

        return view('checkout.confirmed', compact('booking'));
    }
}
