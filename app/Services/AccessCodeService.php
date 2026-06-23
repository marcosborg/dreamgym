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
                'valid_from' => $booking->starts_at->copy()->subMinutes(config('lock.access_start_buffer_minutes', 5)),
                'valid_until' => $booking->ends_at->copy()->addMinutes(config('lock.access_end_buffer_minutes', 5)),
                'provision_status' => AccessCode::PENDING,
            ]
        );
    }

    private function uniqueCode(): string
    {
        $pinLength = min(9, max(4, (int) config('lock.pin_length', 6)));
        $min = 10 ** ($pinLength - 1);
        $max = (10 ** $pinLength) - 1;

        do {
            $code = (string) random_int($min, $max);
        } while (AccessCode::query()->where('code', $code)->where('valid_until', '>=', now())->exists());

        return $code;
    }
}
