<?php

namespace App\Filament\Resources\OpeningHours\Pages;

use App\Filament\Resources\OpeningHours\OpeningHourResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOpeningHour extends EditRecord
{
    protected static string $resource = OpeningHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
