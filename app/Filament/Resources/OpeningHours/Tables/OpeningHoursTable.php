<?php

namespace App\Filament\Resources\OpeningHours\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpeningHoursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room.name')->sortable(),
                TextColumn::make('weekday')->formatStateUsing(fn (int $state): string => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$state])->sortable(),
                TextColumn::make('opens_at'),
                TextColumn::make('closes_at'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
