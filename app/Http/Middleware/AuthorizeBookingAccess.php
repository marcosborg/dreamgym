<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeBookingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $booking = $request->route('booking');
        abort_unless($booking instanceof Booking, 404);
        $user = $request->user();
        $ownsBooking = $user && ($user->is_admin || ($booking->user_id !== null && $booking->user_id === $user->id));
        $ownsGuestSession = $booking->user_id === null && in_array($booking->id, $request->session()->get('guest_booking_ids', []), true);
        abort_unless($ownsBooking || $ownsGuestSession, 403);

        return $next($request)->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
