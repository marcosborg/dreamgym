<?php

namespace App\Filament\Resources\PersonalTrainers;

use App\Filament\Resources\PersonalTrainers\Pages\CreatePersonalTrainer;
use App\Filament\Resources\PersonalTrainers\Pages\EditPersonalTrainer;
use App\Filament\Resources\PersonalTrainers\Pages\ListPersonalTrainers;
use App\Filament\Resources\PersonalTrainers\Schemas\PersonalTrainerForm;
use App\Filament\Resources\PersonalTrainers\Tables\PersonalTrainersTable;
use App\Models\PersonalTrainer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PersonalTrainerResource extends Resource
{
    protected static ?string $model = PersonalTrainer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $navigationLabel = 'Personal Trainer';

    protected static ?string $modelLabel = 'Personal Trainer';

    protected static ?string $pluralModelLabel = 'Personal Trainers';

    public static function form(Schema $schema): Schema
    {
        return PersonalTrainerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonalTrainersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonalTrainers::route('/'),
            'create' => CreatePersonalTrainer::route('/create'),
            'edit' => EditPersonalTrainer::route('/{record}/edit'),
        ];
    }
}
