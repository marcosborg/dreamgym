<?php

namespace App\Filament\Resources\LegalTermSections\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LegalTermSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title_pt')
                    ->label('Título PT')
                    ->required()
                    ->maxLength(160),
                TextInput::make('title_en')
                    ->label('Título EN')
                    ->required()
                    ->maxLength(160),
                Textarea::make('body_pt')
                    ->label('Texto PT')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Textarea::make('body_en')
                    ->label('Texto EN')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
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
