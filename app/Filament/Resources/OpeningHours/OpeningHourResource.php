<?php

namespace App\Filament\Resources\OpeningHours;

use App\Filament\Resources\OpeningHours\Pages\CreateOpeningHour;
use App\Filament\Resources\OpeningHours\Pages\EditOpeningHour;
use App\Filament\Resources\OpeningHours\Pages\ListOpeningHours;
use App\Filament\Resources\OpeningHours\Schemas\OpeningHourForm;
use App\Filament\Resources\OpeningHours\Tables\OpeningHoursTable;
use App\Models\OpeningHour;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OpeningHourResource extends Resource
{
    protected static ?string $model = OpeningHour::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Opening Hours';

    public static function form(Schema $schema): Schema
    {
        return OpeningHourForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningHoursTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpeningHours::route('/'),
            'create' => CreateOpeningHour::route('/create'),
            'edit' => EditOpeningHour::route('/{record}/edit'),
        ];
    }
}
