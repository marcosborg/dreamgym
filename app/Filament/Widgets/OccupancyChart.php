<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class OccupancyChart extends ChartWidget
{
    protected ?string $heading = 'Occupancy by hour';

    protected ?string $description = 'Confirmed bookings in the next 30 days';

    protected string $color = 'success';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $hours = collect(range(7, 22));

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $hours->map(fn (int $hour): int => Booking::query()
                        ->where('status', Booking::STATUS_CONFIRMED)
                        ->whereBetween('starts_at', [now(), now()->addDays(30)])
                        ->whereTime('starts_at', '>=', sprintf('%02d:00:00', $hour))
                        ->whereTime('starts_at', '<', sprintf('%02d:00:00', $hour + 1))
                        ->count())->all(),
                    'borderColor' => '#2f6f55',
                    'backgroundColor' => 'rgba(47, 111, 85, .16)',
                ],
            ],
            'labels' => $hours->map(fn (int $hour): string => sprintf('%02d:00', $hour))->all(),
        ];
    }
}
