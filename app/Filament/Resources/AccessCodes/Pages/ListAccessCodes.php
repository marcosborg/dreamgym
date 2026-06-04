<?php

namespace App\Filament\Resources\AccessCodes\Pages;

use App\Filament\Resources\AccessCodes\AccessCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccessCodes extends ListRecords
{
    protected static string $resource = AccessCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
