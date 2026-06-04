<?php

namespace App\Filament\Resources\AccessCodes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable(),
                TextColumn::make('booking.customer_name')->label('Customer')->searchable(),
                TextColumn::make('valid_from')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('valid_until')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('provision_status')->badge(),
                TextColumn::make('provisioned_at')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
