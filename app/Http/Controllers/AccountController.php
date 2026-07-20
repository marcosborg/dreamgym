<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['room', 'payment', 'accessCode'])
            ->latest('starts_at')
            ->paginate(10);

        $trainerSubmission = $request->user()->personalTrainerSubmissions()->latest()->first();

        return view('account.dashboard', compact('bookings', 'trainerSubmission'));
    }

    public function cancelBooking(Booking $booking, BookingCancellationService $cancellations): RedirectResponse
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        abort_unless($booking->canBeCancelledByCustomer(), 422);

        $cancellations->cancel($booking);

        return redirect()
            ->route('account.dashboard')
            ->with('status', __('site.booking_cancelled'));
    }
}
