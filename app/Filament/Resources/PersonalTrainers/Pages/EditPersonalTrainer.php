<?php

namespace App\Filament\Resources\PersonalTrainers\Pages;

use App\Filament\Resources\PersonalTrainers\PersonalTrainerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonalTrainer extends EditRecord
{
    protected static string $resource = PersonalTrainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
