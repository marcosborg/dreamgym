<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingCreditService;
use App\Services\SandboxPaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $booking = Booking::create($data);
            app(BookingCreditService::class)->coverAdminBooking($booking);
            if ($booking->status === Booking::STATUS_CONFIRMED && $booking->payment_status === 'paid') {
                app(SandboxPaymentService::class)->confirmCoveredBooking($booking);
            }

            return $booking->fresh();
        });
    }
}
