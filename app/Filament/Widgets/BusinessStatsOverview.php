<?php

namespace App\Filament\Widgets;

use App\Models\AccessCode;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Today at a glance';

    protected function getStats(): array
    {
        $todayBookings = Booking::query()
            ->whereDate('starts_at', today())
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->count();

        $monthRevenue = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount_cents');

        $nextBooking = Booking::query()
            ->where('starts_at', '>=', now())
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->orderBy('starts_at')
            ->first();

        $activeCodes = AccessCode::query()
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->count();

        return [
            Stat::make('Bookings today', $todayBookings)
                ->description('Pending and confirmed hours')
                ->icon(Heroicon::OutlinedCalendarDateRange)
                ->chart($this->dailyBookingTrend())
                ->color('primary'),
            Stat::make('Revenue this month', number_format($monthRevenue / 100, 2, ',', ' ').' EUR')
                ->description('Sandbox paid payments')
                ->icon(Heroicon::OutlinedCurrencyEuro)
                ->chart($this->dailyRevenueTrend())
                ->color('success'),
            Stat::make('Next booking', $nextBooking?->starts_at->format('d/m H:i') ?? 'No upcoming booking')
                ->description($nextBooking?->customer_name ?? 'Agenda is clear')
                ->icon(Heroicon::OutlinedClock)
                ->color('info'),
            Stat::make('Active access codes', $activeCodes)
                ->description(Room::query()->where('is_active', true)->count().' active room(s)')
                ->icon(Heroicon::OutlinedKey)
                ->color('warning'),
        ];
    }

    private function dailyBookingTrend(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => Booking::query()
                ->whereDate('starts_at', now()->subDays($daysAgo))
                ->where('status', '!=', Booking::STATUS_CANCELLED)
                ->count())
            ->all();
    }

    private function dailyRevenueTrend(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): float => Payment::query()
                ->where('status', 'paid')
                ->whereDate('paid_at', now()->subDays($daysAgo))
                ->sum('amount_cents') / 100)
            ->all();
    }
}
