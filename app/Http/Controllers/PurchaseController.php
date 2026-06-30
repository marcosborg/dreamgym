<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Services\Payments\IfthenpayPaymentService;
use App\Services\Payments\PaymentProvider;
use App\Services\ProductCatalog;
use App\Services\SandboxPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function store(Request $request, ProductCatalog $catalog, PaymentProvider $provider, IfthenpayPaymentService $ifthenpay): RedirectResponse
    {
        $rules = [
            'product_id' => ['nullable', 'integer'],
            'product_type' => ['required_without:product_id', 'nullable', 'in:session_pack,membership'],
            'customer_name' => [Auth::check() ? 'nullable' : 'required', 'string', 'max:120'],
            'customer_email' => [Auth::check() ? 'nullable' : 'required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'password' => [Auth::check() ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        $data = $request->validate($rules);

        $room = Room::query()->where('is_active', true)->firstOrFail();
        $user = Auth::user();

        if (! $user) {
            $user = User::query()->where('email', $data['customer_email'])->first();

            if ($user) {
                if (! Auth::attempt(['email' => $data['customer_email'], 'password' => $data['password']])) {
                    throw ValidationException::withMessages([
                        'customer_email' => __('auth.failed'),
                    ]);
                }

                $user = Auth::user();
            } else {
                $user = User::create([
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'phone' => $data['customer_phone'] ?? null,
                    'password' => Hash::make($data['password']),
                    'is_admin' => false,
                ]);

                Auth::login($user);
            }
        }

        $product = ! empty($data['product_id'])
            ? $catalog->findPurchaseProduct((int) $data['product_id'], $room)
            : ($data['product_type'] === ProductCatalog::SESSION_PACK
                ? $catalog->sessionPack($room)
                : $catalog->membership($room));

        abort_unless($product && $product['active'], 422, __('site.product_unavailable'));

        $payment = $provider->isIfthenpay()
            ? $ifthenpay->createPurchasePayment($user, $product, $room)
            : Payment::create([
                'user_id' => $user->id,
                'product_type' => $product['type'],
                'provider' => 'sandbox_mbway_placeholder',
                'reference' => 'DG-'.Str::upper(Str::random(10)),
                'amount_cents' => $product['price_cents'],
                'currency' => $product['currency'] ?? $room->currency,
                'status' => 'pending',
                'metadata' => [
                    'product_id' => $product['id'],
                    'label' => $product['name'],
                    'credits' => $product['credits'] ?? null,
                    'days' => $product['days'] ?? null,
                ],
            ]);

        return redirect()->route('purchase.checkout', $payment);
    }

    public function checkout(Payment $payment, PaymentProvider $provider): View
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        return view('purchases.checkout', [
            'payment' => $payment,
            'paymentProvider' => $provider->isIfthenpay() ? 'ifthenpay' : 'sandbox',
        ]);
    }

    public function complete(Payment $payment, SandboxPaymentService $payments, IfthenpayPaymentService $ifthenpay, PaymentProvider $provider): RedirectResponse
    {
        $rules = [
            'terms_accepted' => ['accepted'],
        ];

        if ($provider->isIfthenpay()) {
            $rules['payment_method'] = ['required', 'in:multibanco,mbway'];
            $rules['mbway_phone'] = ['required_if:payment_method,mbway', 'nullable', 'string', 'max:30'];
        }

        $data = request()->validate($rules);

        abort_unless($payment->user_id === Auth::id(), 403);

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

            return redirect()->route('purchase.checkout', $payment)
                ->with('status', __('site.payment_pending_confirmation'));
        }

        $payments->completePurchase($payment);

        return redirect()->route('purchase.confirmed', $payment);
    }

    public function confirmed(Payment $payment): View
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        return view('purchases.confirmed', compact('payment'));
    }
}
