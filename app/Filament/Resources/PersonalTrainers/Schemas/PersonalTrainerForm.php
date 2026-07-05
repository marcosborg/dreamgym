<?php

namespace App\Filament\Resources\PersonalTrainers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PersonalTrainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(120),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(160),
                TextInput::make('phone')
                    ->label('Telefone')
                    ->maxLength(40),
                TextInput::make('sort_order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
                Textarea::make('bio')
                    ->label('Bio / descrição')
                    ->rows(6)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
