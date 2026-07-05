<?php

namespace App\Filament\Resources\PersonalTrainers\Pages;

use App\Filament\Resources\PersonalTrainers\PersonalTrainerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalTrainers extends ListRecords
{
    protected static string $resource = PersonalTrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
