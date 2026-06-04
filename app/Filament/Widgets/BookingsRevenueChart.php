<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class BookingsRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Bookings and revenue';

    protected ?string $description = 'Last 14 days';

    protected string $color = 'primary';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo));

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $days->map(fn ($day): int => Booking::query()
                        ->whereDate('starts_at', $day)
                        ->where('status', '!=', Booking::STATUS_CANCELLED)
                        ->count())->all(),
                    'backgroundColor' => '#0870ad',
                ],
                [
                    'label' => 'Revenue EUR',
                    'data' => $days->map(fn ($day): float => Payment::query()
                        ->where('status', 'paid')
                        ->whereDate('paid_at', $day)
                        ->sum('amount_cents') / 100)->all(),
                    'backgroundColor' => '#151515',
                ],
            ],
            'labels' => $days->map(fn ($day): string => $day->format('d/m'))->all(),
        ];
    }
}
