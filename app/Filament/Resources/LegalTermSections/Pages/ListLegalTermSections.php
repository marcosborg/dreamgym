<?php

namespace App\Filament\Resources\LegalTermSections\Pages;

use App\Filament\Resources\LegalTermSections\LegalTermSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegalTermSections extends ListRecords
{
    protected static string $resource = LegalTermSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
