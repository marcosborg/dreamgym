<?php

namespace App\Filament\Resources\BlackoutPeriods\Pages;

use App\Filament\Resources\BlackoutPeriods\BlackoutPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlackoutPeriods extends ListRecords
{
    protected static string $resource = BlackoutPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
