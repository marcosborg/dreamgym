<?php

namespace App\Filament\Resources\AccessCodes\Pages;

use App\Filament\Resources\AccessCodes\AccessCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccessCode extends EditRecord
{
    protected static string $resource = AccessCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
