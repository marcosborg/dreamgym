<?php

namespace App\Services;

use App\Models\AccessCode;
use App\Models\Booking;

class AccessCodeService
{
    public function createForBooking(Booking $booking): AccessCode
    {
        return AccessCode::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'code' => $this->uniqueCode(),
                'valid_from' => $booking->starts_at->copy()->subMinutes(5),
                'valid_until' => $booking->ends_at->copy()->addMinutes(5),
                'provision_status' => 'pending',
            ]
        );
    }

    private function uniqueCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (AccessCode::query()->where('code', $code)->where('valid_until', '>=', now())->exists());

        return $code;
    }
}
