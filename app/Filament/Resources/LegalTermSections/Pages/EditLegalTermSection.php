<?php

namespace App\Filament\Resources\LegalTermSections\Pages;

use App\Filament\Resources\LegalTermSections\LegalTermSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLegalTermSection extends EditRecord
{
    protected static string $resource = LegalTermSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
