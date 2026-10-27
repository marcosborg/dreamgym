<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'creditMovements';

    protected static ?string $title = 'Histórico de créditos';

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s'),
            TextColumn::make('kind')->label('Tipo')->formatStateUsing(fn ($state) => __('site.credit_kind_'.$state)),
            TextColumn::make('amount')->label('Movimento')->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').$state),
            TextColumn::make('balance')->label('Saldo'), TextColumn::make('booking_id')->label('Reserva'),
            TextColumn::make('reason')->label('Motivo')->formatStateUsing(fn ($state) => str_starts_with($state, 'manual:') ? substr($state, 7) : __('site.credit_reason_'.$state))->wrap(),
            TextColumn::make('actor_id')->label('Utilizador responsável'),
        ]);
    }
}
