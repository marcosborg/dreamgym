<?php

namespace App\Services;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class BookingMail
{
    public static function confirmed(Booking $booking): void
    {
        if ($booking->suppress_notifications) {
            return;
        }
        Mail::to($booking->customer_email)->bcc('info@dreamgym.pt')->send(new BookingConfirmed($booking));
    }
}
