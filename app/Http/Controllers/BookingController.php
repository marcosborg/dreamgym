<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\SandboxPaymentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use App\Models\User;

class BookingController extends Controller
{
    public function index(Request $request, AvailabilityService $availability): View
    {
        $room = Room::query()->where('is_active', true)->firstOrFail();
        $date = $request->query('date', now()->toDateString());
        $slots = $availability->slotsForDate($room, $date);

        return view('bookings.index', compact('room', 'date', 'slots'));
    }

    public function store(Request $request, AvailabilityService $availability, SandboxPaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'starts_at' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:create_account,1', 'string', 'min:8', 'confirmed'],
        ]);

        $room = Room::query()->where('is_active', true)->findOrFail($data['room_id']);
        $startsAt = Carbon::parse($data['starts_at'], config('app.timezone'));
        $endsAt = $startsAt->copy()->addMinutes(AvailabilityService::SLOT_MINUTES);

        abort_unless($availability->isAvailableRange($room, $startsAt, $endsAt), 422, __('site.slot_unavailable'));

        $user = Auth::user();

        if (! $user && ($data['create_account'] ?? false)) {
            $user = User::firstOrCreate(
                ['email' => $data['customer_email']],
                [
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'] ?? null,
                    'password' => Hash::make($data['password']),
                    'is_admin' => false,
                ]
            );

            Auth::login($user);
        }

        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user?->id,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'locale' => app()->getLocale(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => Booking::STATUS_PENDING,
            'payment_status' => 'pending',
            'price_cents' => $room->slot_price_cents,
            'currency' => $room->currency,
        ]);

        $payments->createPayment($booking);

        return redirect()->route('checkout.show', $booking);
    }
}
