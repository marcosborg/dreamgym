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
        $digits = array_values(array_unique(str_split((string) config('lock.pin_digits', '123456'))));
        if (count($digits) < 2 || preg_match('/[^0-9]/', implode('', $digits))) {
            throw new \InvalidArgumentException('LOCK_PIN_DIGITS must contain at least two distinct digits.');
        }

        do {
            $code = '';
            for ($i = 0; $i < $pinLength; $i++) {
                $code .= $digits[random_int(0, count($digits) - 1)];
            }
        } while (AccessCode::query()->where('code', $code)->where('valid_until', '>=', now())->exists());

        return $code;
    }
}
