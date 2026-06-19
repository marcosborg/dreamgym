<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\ProductCatalog;
use App\Services\SandboxPaymentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\User;

class BookingController extends Controller
{
    public function index(Request $request, AvailabilityService $availability, ProductCatalog $catalog): View
    {
        $room = Room::query()->where('is_active', true)->firstOrFail();
        $date = $request->query('date', now()->toDateString());
        $slots = $availability->slotsForDate($room, $date);
        $products = [
            'session_pack' => $catalog->sessionPack($room),
            'membership' => $catalog->membership($room),
            'group_hour' => $catalog->groupHour($room),
        ];

        return view('bookings.index', compact('room', 'date', 'slots', 'products'));
    }

    public function store(Request $request, AvailabilityService $availability, SandboxPaymentService $payments, ProductCatalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'starts_at' => ['required', 'date'],
            'booking_type' => ['required', 'in:single_hour,group_hour'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'bringing_children' => ['required', 'boolean'],
            'children_responsibility_accepted' => ['required_if:bringing_children,1', 'accepted_if:bringing_children,1'],
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:create_account,1', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['sometimes', 'accepted'],
        ]);

        $room = Room::query()->where('is_active', true)->findOrFail($data['room_id']);
        $startsAt = Carbon::parse($data['starts_at'], config('app.timezone'));
        $endsAt = $startsAt->copy()->addMinutes(AvailabilityService::SLOT_MINUTES);
        $isGroup = $data['booking_type'] === Booking::TYPE_GROUP_HOUR;
        $seatsReserved = $isGroup ? $room->capacity : 1;
        $groupProduct = $catalog->groupHour($room);

        abort_unless(! $isGroup || $groupProduct['active'], 422, __('site.product_unavailable'));

        abort_unless($availability->isAvailableRange(
            $room,
            $startsAt,
            $endsAt,
            seatsRequested: $seatsReserved,
            requiresEmptySlot: $isGroup,
        ), 422, __('site.slot_unavailable'));

        $user = Auth::user()?->fresh();

        if (! $user && ($data['create_account'] ?? false)) {
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

        $paidWith = null;
        $status = Booking::STATUS_PENDING;
        $paymentStatus = 'pending';
        $priceCents = $isGroup ? $groupProduct['price_cents'] : $room->slot_price_cents;

        if (! $isGroup && $user?->hasActiveMembership()) {
            $request->validate([
                'terms_accepted' => ['accepted'],
            ]);

            $paidWith = Booking::PAID_WITH_MEMBERSHIP;
            $status = Booking::STATUS_CONFIRMED;
            $paymentStatus = 'paid';
            $priceCents = 0;
        } elseif (! $isGroup && $user && $user->session_credits > 0) {
            $request->validate([
                'terms_accepted' => ['accepted'],
            ]);

            $user->decrement('session_credits');
            $paidWith = Booking::PAID_WITH_CREDITS;
            $status = Booking::STATUS_CONFIRMED;
            $paymentStatus = 'paid';
            $priceCents = 0;
        } else {
            $paidWith = Booking::PAID_WITH_PAYMENT;
        }

        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user?->id,
            'booking_type' => $data['booking_type'],
            'seats_reserved' => $seatsReserved,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'locale' => app()->getLocale(),
            'bringing_children' => (bool) $data['bringing_children'],
            'children_responsibility_accepted_at' => (bool) $data['bringing_children'] ? now() : null,
            'terms_accepted_at' => $request->boolean('terms_accepted') ? now() : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'paid_with' => $paidWith,
            'price_cents' => $priceCents,
            'currency' => $room->currency,
        ]);

        if ($booking->payment_status === 'paid') {
            $payments->confirmCoveredBooking($booking);

            return redirect()->route('booking.confirmed', $booking);
        }

        $payments->createPayment($booking);

        return redirect()->route('checkout.show', $booking);
    }
}
