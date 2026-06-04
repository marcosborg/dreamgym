<?php

namespace App\Filament\Resources\BlackoutPeriods;

use App\Filament\Resources\BlackoutPeriods\Pages\CreateBlackoutPeriod;
use App\Filament\Resources\BlackoutPeriods\Pages\EditBlackoutPeriod;
use App\Filament\Resources\BlackoutPeriods\Pages\ListBlackoutPeriods;
use App\Filament\Resources\BlackoutPeriods\Schemas\BlackoutPeriodForm;
use App\Filament\Resources\BlackoutPeriods\Tables\BlackoutPeriodsTable;
use App\Models\BlackoutPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BlackoutPeriodResource extends Resource
{
    protected static ?string $model = BlackoutPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $navigationLabel = 'Blocked Periods';

    public static function form(Schema $schema): Schema
    {
        return BlackoutPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlackoutPeriodsTable::configure($table);
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
            'index' => ListBlackoutPeriods::route('/'),
            'create' => CreateBlackoutPeriod::route('/create'),
            'edit' => EditBlackoutPeriod::route('/{record}/edit'),
        ];
    }
}
