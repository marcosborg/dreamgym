<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\Payments\IfthenpayPaymentService;
use App\Services\Payments\PaymentProvider;
use App\Services\ProductCatalog;
use App\Services\SandboxPaymentService;
use App\Services\SessionCreditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request, AvailabilityService $availability, ProductCatalog $catalog): View
    {
        $room = Room::query()->where('is_active', true)->firstOrFail();
        $date = $request->query('date', now()->toDateString());
        $slots = $availability->slotsForDate($room, $date);
        $products = [
            'single_hour' => $catalog->singleHour($room),
            'session_pack' => $catalog->sessionPack($room),
            'membership' => $catalog->membership($room),
            'group_hour' => $catalog->groupHour($room),
        ];
        $purchaseProducts = $catalog->purchaseProducts($room);

        return view('bookings.index', compact('room', 'date', 'slots', 'products', 'purchaseProducts'));
    }

    public function store(Request $request, AvailabilityService $availability, SandboxPaymentService $payments, ProductCatalog $catalog): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'starts_at' => ['required_without:slots', 'date', 'prohibits:slots'],
            'slots' => ['required_without:starts_at', 'array', 'min:1', 'max:24'],
            'slots.*' => ['required', 'date_format:Y-m-d H:i:s', 'distinct'],
            'booking_type' => ['required', 'in:single_hour,group_hour'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'bringing_children' => ['required', 'boolean'],
            'children_responsibility_accepted' => ['required_if:bringing_children,1', 'accepted_if:bringing_children,1'],
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:create_account,1', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['sometimes', 'accepted'],
            'age_authorization_accepted' => ['required', 'accepted'],
        ], ['age_authorization_accepted.required' => __('site.age_authorization_required'),
            'age_authorization_accepted.accepted' => __('site.age_authorization_required')]);

        $room = Room::query()->where('is_active', true)->findOrFail($data['room_id']);
        $selectedStarts = collect($data['slots'] ?? [$data['starts_at']])
            ->map(fn ($value) => Carbon::parse($value, config('app.timezone')))->sort()->values();
        $multiple = $selectedStarts->count() > 1;
        $isGroup = $data['booking_type'] === Booking::TYPE_GROUP_HOUR;
        $seatsReserved = $isGroup ? $room->capacity : 1;
        $groupProduct = $catalog->groupHour($room);

        abort_unless(! $isGroup || $groupProduct['active'], 422, __('site.product_unavailable'));

        if ($multiple && (! Auth::check() || $isGroup)) {
            throw ValidationException::withMessages(['slots' => __('site.multi_requires_credits')]);
        }

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

        return DB::transaction(function () use ($request, $data, $room, $selectedStarts, $multiple, $isGroup, $seatsReserved, $groupProduct, $catalog, $payments, $user) {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $user = $user ? User::query()->lockForUpdate()->findOrFail($user->id) : null;
            if ($multiple && Booking::where('user_id', $user->id)->where('room_id', $room->id)
                ->where('status', Booking::STATUS_CONFIRMED)->whereIn('starts_at', $selectedStarts)->exists()) {
                throw ValidationException::withMessages(['slots' => __('site.multi_already_booked')]);
            }
            $bookings = collect();
            foreach ($selectedStarts as $startsAt) {
                $endsAt = $startsAt->copy()->addMinutes(AvailabilityService::SLOT_MINUTES);
                abort_unless(app(AvailabilityService::class)->isAvailableRange(
                    $room, $startsAt, $endsAt, seatsRequested: $seatsReserved, requiresEmptySlot: $isGroup,
                ), 422, __('site.slot_unavailable'));
                $creditLotId = null;
                $sessionCredits = app(SessionCreditService::class);
                $paidWith = null;
                $status = Booking::STATUS_PENDING;
                $paymentStatus = 'pending';
                $singleHourProduct = $catalog->singleHour($room);
                $priceCents = $isGroup ? $groupProduct['price_cents'] : $singleHourProduct['price_cents'];

                if (! $isGroup && $user?->hasActiveMembership() && $startsAt->lessThan($user->membership_expires_at)) {
                    $request->validate([
                        'terms_accepted' => ['accepted'],
                    ]);

                    $user->decrement('membership_credits');
                    $paidWith = Booking::PAID_WITH_MEMBERSHIP;
                    $status = Booking::STATUS_CONFIRMED;
                    $paymentStatus = 'paid';
                    $priceCents = 0;
                } elseif (! $isGroup && $user && $sessionCredits->availableFor($user, $startsAt) > 0) {
                    $request->validate([
                        'terms_accepted' => ['accepted'],
                    ]);

                    $creditLotId = $sessionCredits->consume($user, $startsAt);
                    $paidWith = Booking::PAID_WITH_CREDITS;
                    $status = Booking::STATUS_CONFIRMED;
                    $paymentStatus = 'paid';
                    $priceCents = 0;
                } else {
                    if ($multiple) {
                        throw ValidationException::withMessages(['slots' => __('site.multi_insufficient_credits')]);
                    }
                    $paidWith = Booking::PAID_WITH_PAYMENT;
                }

                $booking = Booking::create([
                    'room_id' => $room->id,
                    'user_id' => $user?->id,
                    'session_credit_lot_id' => $creditLotId,
                    'booking_type' => $data['booking_type'],
                    'seats_reserved' => $seatsReserved,
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'locale' => app()->getLocale(),
                    'bringing_children' => (bool) $data['bringing_children'],
                    'children_responsibility_accepted_at' => (bool) $data['bringing_children'] ? now() : null,
                    'terms_accepted_at' => $request->boolean('terms_accepted') ? now() : null,
                    'age_authorization_accepted_at' => now(),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'paid_with' => $paidWith,
                    'price_cents' => $priceCents,
                    'currency' => $room->currency,
                    'payment_expires_at' => $status === Booking::STATUS_PENDING ? now()->addMinutes(15)->min($startsAt) : null,
                ]);

                $bookings->push($booking);
            }

            // All slots and credits must succeed before provisioning any access or sending mail.
            foreach ($bookings as $booking) {
                if (! $user) {
                    $request->session()->push('guest_booking_ids', $booking->id);
                }
                if ($booking->payment_status === 'paid') {
                    $payments->confirmCoveredBooking($booking);
                }
            }
            if ($multiple) {
                return redirect()->route('account.dashboard')->with('status', __('site.multi_confirmed', ['count' => $bookings->count()]));
            }
            $booking = $bookings->first();
            if ($booking->payment_status === 'paid') {
                return redirect()->route('booking.confirmed', $booking);
            }

            if (app(PaymentProvider::class)->isIfthenpay()) {
                app(IfthenpayPaymentService::class)->createPayment($booking);
            } else {
                $payments->createPayment($booking);
            }

            return redirect()->route('checkout.show', $booking);
        });
    }
}
