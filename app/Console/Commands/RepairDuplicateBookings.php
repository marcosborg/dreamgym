<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\SessionCreditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairDuplicateBookings extends Command
{
    protected $signature = 'bookings:repair-duplicates {--ids= : Explicit comma-separated duplicate booking IDs} {--apply : Apply the audited credit corrections}';

    protected $description = 'Review or correct selected duplicate credit bookings, keeping the original and restoring one credit';

    public function handle(): int
    {
        $ids = array_filter(explode(',', (string) $this->option('ids')), fn ($id) => ctype_digit($id));
        foreach ($ids as $id) {
            DB::transaction(function () use ($id) {
                $candidate = Booking::findOrFail($id);
                Room::whereKey($candidate->room_id)->lockForUpdate()->firstOrFail();
                $user = User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
                $booking = Booking::whereKey($id)->lockForUpdate()->firstOrFail();
                if ($booking->status !== Booking::STATUS_CONFIRMED || $booking->duplicate_of_id || $booking->payment_status !== 'paid'
                    || $booking->booking_type !== Booking::TYPE_SINGLE_HOUR
                    || ! in_array($booking->paid_with, [Booking::PAID_WITH_CREDITS, Booking::PAID_WITH_MEMBERSHIP])) {
                    $this->line('Skipped booking '.$id.' (not an unrepaired credit booking).');

                    return;
                }
                $original = Booking::where('user_id', $user->id)->where('room_id', $booking->room_id)
                    ->where('starts_at', $booking->starts_at)->where('ends_at', $booking->ends_at)
                    ->where('booking_type', $booking->booking_type)->where('status', Booking::STATUS_CONFIRMED)
                    ->where('payment_status', 'paid')->where('paid_with', $booking->paid_with)
                    ->where('id', '<', $booking->id)->whereBetween('created_at', [$booking->created_at->copy()->subMinute(), $booking->created_at])
                    ->oldest('id')->first();
                if (! $original) {
                    $this->warn('Skipped booking '.$id.' (no matching original).');

                    return;
                }
                $this->line('Duplicate '.$id.' of '.$original->id.': restore one '.$booking->paid_with.' credit.');
                if (! $this->option('apply')) {
                    return;
                }
                if ($booking->paid_with === Booking::PAID_WITH_MEMBERSHIP) {
                    $user->increment('membership_credits');
                } else {
                    app(SessionCreditService::class)->refund($user, $booking);
                }
                $booking->update(['duplicate_of_id' => $original->id, 'status' => Booking::STATUS_CANCELLED, 'cancelled_at' => now()]);
                Log::info('Duplicate booking credit restored.', ['booking_id' => $booking->id, 'original_id' => $original->id, 'user_id' => $user->id, 'credit_type' => $booking->paid_with]);
            });
        }

        return self::SUCCESS;
    }
}
