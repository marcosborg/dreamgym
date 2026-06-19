<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(120),
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        Product::TYPE_SINGLE_HOUR => 'Hora individual',
                        Product::TYPE_SESSION_PACK => 'Pack de sessões',
                        Product::TYPE_MEMBERSHIP => 'Mensalidade',
                        Product::TYPE_GROUP_HOUR => 'Grupo privado',
                    ])
                    ->required(),
                TextInput::make('price_cents')
                    ->label('Preço (cêntimos)')
                    ->helperText('Exemplo: 1200 = 12,00 EUR.')
                    ->numeric()
                    ->required(),
                TextInput::make('currency')
                    ->label('Moeda')
                    ->required()
                    ->maxLength(3)
                    ->default('EUR'),
                TextInput::make('credits')
                    ->label('Créditos')
                    ->helperText('Usado em packs de sessões.')
                    ->numeric(),
                TextInput::make('days')
                    ->label('Dias de validade')
                    ->helperText('Usado em mensalidades.')
                    ->numeric(),
                TextInput::make('seats')
                    ->label('Lugares')
                    ->helperText('Usado em hora individual/grupo quando aplicável.')
                    ->numeric(),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
            ])
            ->columns(2);
    }
}
