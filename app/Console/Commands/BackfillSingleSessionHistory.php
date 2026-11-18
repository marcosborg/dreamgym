<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\SingleSessionHistory;
use Illuminate\Console\Command;

class BackfillSingleSessionHistory extends Command
{
    protected $signature = 'credits:backfill-single-sessions';

    protected $description = 'Record historical single-session purchases and usage without changing balances';

    public function handle(SingleSessionHistory $history): int
    {
        Booking::where('booking_type', Booking::TYPE_SINGLE_HOUR)
            ->where('paid_with', Booking::PAID_WITH_PAYMENT)->where('payment_status', 'paid')
            ->whereNotNull('confirmed_at')->whereNotNull('user_id')->whereNull('duplicate_of_id')
            ->chunkById(100, function ($bookings) use ($history) {
                foreach ($bookings as $booking) {
                    $history->record($booking);
                }
            });
        $this->info('Histórico das sessões individuais atualizado.');

        return self::SUCCESS;
    }
}
