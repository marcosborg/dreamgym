<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Room;
use App\Services\ProductCatalog;
use App\Services\SandboxPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\User;

class PurchaseController extends Controller
{
    public function store(Request $request, ProductCatalog $catalog): RedirectResponse
    {
        $rules = [
            'product_type' => ['required', 'in:session_pack,membership'],
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

        $product = $data['product_type'] === ProductCatalog::SESSION_PACK
            ? $catalog->sessionPack($room)
            : $catalog->membership($room);

        abort_unless($product['active'], 422, __('site.product_unavailable'));

        $payment = Payment::create([
            'user_id' => $user->id,
            'product_type' => $data['product_type'],
            'provider' => 'sandbox_mbway_placeholder',
            'reference' => 'DG-' . Str::upper(Str::random(10)),
            'amount_cents' => $product['price_cents'],
            'currency' => $room->currency,
            'status' => 'pending',
            'metadata' => [
                'label' => $product['name'],
                'credits' => $product['credits'] ?? null,
                'days' => $product['days'] ?? null,
            ],
        ]);

        return redirect()->route('purchase.checkout', $payment);
    }

    public function checkout(Payment $payment): View
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        return view('purchases.checkout', compact('payment'));
    }

    public function complete(Payment $payment, SandboxPaymentService $payments): RedirectResponse
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        $payments->completePurchase($payment);

        return redirect()->route('purchase.confirmed', $payment);
    }

    public function confirmed(Payment $payment): View
    {
        abort_unless($payment->user_id === Auth::id(), 403);

        return view('purchases.confirmed', compact('payment'));
    }
}
