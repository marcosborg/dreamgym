<?php

namespace App\Filament\Resources\AccessCodes\Pages;

use App\Filament\Resources\AccessCodes\AccessCodeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccessCode extends ViewRecord
{
    protected static string $resource = AccessCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
