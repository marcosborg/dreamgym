<?php

namespace App\Filament\Resources\BlackoutPeriods\Pages;

use App\Filament\Resources\BlackoutPeriods\BlackoutPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlackoutPeriod extends EditRecord
{
    protected static string $resource = BlackoutPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
