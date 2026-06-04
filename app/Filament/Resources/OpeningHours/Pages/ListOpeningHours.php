<?php

namespace App\Filament\Resources\OpeningHours\Pages;

use App\Filament\Resources\OpeningHours\OpeningHourResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpeningHours extends ListRecords
{
    protected static string $resource = OpeningHourResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
