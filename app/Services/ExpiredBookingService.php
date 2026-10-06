<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class ExpiredBookingService
{
    public function expire(): int
    {
        $count = 0;
        Booking::expiredUnpaid()->select('id', 'room_id')->eachById(function (Booking $candidate) use (&$count) {
            $count += DB::transaction(function () use ($candidate) {
                // Same first lock as booking creation and payment confirmation.
                Room::whereKey($candidate->room_id)->lockForUpdate()->firstOrFail();
                $booking = Booking::whereKey($candidate->id)->expiredUnpaid()->lockForUpdate()->first();
                if (! $booking || $booking->payment?->status === 'paid' || $booking->accessCode) {
                    return 0;
                }
                $booking->update(['status' => Booking::STATUS_CANCELLED, 'cancelled_at' => now()]);

                return 1;
            });
        });

        return $count;
    }
}
