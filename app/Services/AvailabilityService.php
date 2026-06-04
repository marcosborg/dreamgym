<?php

namespace App\Services;

use App\Models\BlackoutPeriod;
use App\Models\Booking;
use App\Models\OpeningHour;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public const SLOT_MINUTES = 30;

    public function slotsForDate(Room $room, string $date): Collection
    {
        $day = Carbon::parse($date, config('app.timezone'))->startOfDay();
        $hours = OpeningHour::query()
            ->where('room_id', $room->id)
            ->where('weekday', $day->dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (! $hours) {
            return collect();
        }

        $opensAt = Carbon::parse($day->toDateString().' '.$hours->opens_at, config('app.timezone'));
        $closesAt = Carbon::parse($day->toDateString().' '.$hours->closes_at, config('app.timezone'));
        $lastStart = $closesAt->copy()->subMinutes(self::SLOT_MINUTES);

        if ($lastStart->lessThan($opensAt)) {
            return collect();
        }

        return collect(CarbonPeriod::create($opensAt, self::SLOT_MINUTES.' minutes', $lastStart))
            ->map(fn (Carbon $start) => [
                'starts_at' => $start->copy(),
                'ends_at' => $start->copy()->addMinutes(self::SLOT_MINUTES),
                'available' => ! $this->hasConflict($room, $start, $start->copy()->addMinutes(self::SLOT_MINUTES)),
            ])
            ->values();
    }

    public function isAvailable(Room $room, Carbon $startsAt, ?Booking $ignoreBooking = null): bool
    {
        $endsAt = $startsAt->copy()->addMinutes(self::SLOT_MINUTES);

        return ! $this->hasConflict($room, $startsAt, $endsAt, $ignoreBooking);
    }

    public function isAvailableRange(Room $room, Carbon $startsAt, Carbon $endsAt, ?Booking $ignoreBooking = null): bool
    {
        if ($endsAt->diffInMinutes($startsAt) % self::SLOT_MINUTES !== 0) {
            return false;
        }

        $slots = $this->slotsForDate($room, $startsAt->toDateString());
        $selectedStarts = collect(CarbonPeriod::create($startsAt, self::SLOT_MINUTES.' minutes', $endsAt->copy()->subMinutes(self::SLOT_MINUTES)))
            ->map(fn (Carbon $slot) => $slot->format('Y-m-d H:i:s'));

        return $selectedStarts->every(fn (string $slotStart): bool => $slots->contains(
            fn (array $slot): bool => $slot['starts_at']->format('Y-m-d H:i:s') === $slotStart && $slot['available']
        )) && ! $this->hasConflict($room, $startsAt, $endsAt, $ignoreBooking);
    }

    public function hasConflict(Room $room, Carbon $startsAt, Carbon $endsAt, ?Booking $ignoreBooking = null): bool
    {
        $bookingConflict = Booking::query()
            ->where('room_id', $room->id)
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->when($ignoreBooking, fn ($query) => $query->whereKeyNot($ignoreBooking->id))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($bookingConflict) {
            return true;
        }

        return BlackoutPeriod::query()
            ->where('room_id', $room->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }
}
