<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;

class BookingCancelled extends Mailable
{
    public function __construct(public Booking $booking)
    {
        $this->locale($booking->locale ?: 'pt');
    }

    public function build()
    {
        return $this->subject(__('site.cancelled_email_subject'))->view('emails.booking-cancelled');
    }
}
