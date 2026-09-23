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
                    ->label('Nome (português)')
                    ->required()
                    ->maxLength(120),
                TextInput::make('name_en')
                    ->label('Nome (inglês)')
                    ->helperText('Se ficar vazio, será usado o nome em português.')
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
                    ->helperText('Usado em packs e mensalidades. Cada reserva individual desconta um crédito.')
                    ->numeric(),
                TextInput::make('days')
                    ->label('Dias de validade')
                    ->helperText('Sessões e packs: validade fixa de 90 dias por compra. Nas mensalidades, define a duração do período comprado.')
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
