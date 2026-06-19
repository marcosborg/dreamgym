<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                Textarea::make('description')->columnSpanFull(),
                TextInput::make('capacity')->numeric()->required()->default(1),
                TextInput::make('slot_price_cents')
                    ->label('Preço por hora (cêntimos)')
                    ->helperText('Exemplo: 1200 = 12,00 EUR. Este preço é usado como fallback se não existir produto de hora individual.')
                    ->numeric()
                    ->required()
                    ->default(1200),
                TextInput::make('currency')->required()->maxLength(3)->default('EUR'),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
