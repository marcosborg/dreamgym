<?php

namespace App\Console\Commands;

use App\Services\ExpiredBookingService;
use Illuminate\Console\Command;

class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire';

    protected $description = 'Cancel unpaid bookings after their payment hold expires';

    public function handle(ExpiredBookingService $service): int
    {
        $this->info('Expired bookings: '.$service->expire());

        return self::SUCCESS;
    }
}
