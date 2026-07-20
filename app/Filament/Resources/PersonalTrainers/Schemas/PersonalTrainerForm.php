<?php

namespace App\Filament\Resources\PersonalTrainers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
                FileUpload::make('photo_path')
                    ->label('Fotografia')
                    ->disk('public')
                    ->directory('personal-trainers/profiles')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios(['1:1'])
                    ->maxSize(4096)
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label('Conta associada')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(120),
                TextInput::make('title_pt')->label('Título profissional (PT)')->maxLength(160),
                TextInput::make('title_en')->label('Título profissional (EN)')->maxLength(160),
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
                Textarea::make('specialties_pt')->label('Especialidades (PT)')->rows(3),
                Textarea::make('specialties_en')->label('Especialidades (EN)')->rows(3),
                Textarea::make('bio_pt')
                    ->label('Bio / descrição (PT)')
                    ->rows(6)
                    ->columnSpanFull(),
                Textarea::make('bio_en')->label('Bio / descrição (EN)')->rows(6)->columnSpanFull(),
                Toggle::make('show_email')->label('Mostrar email')->default(true),
                Toggle::make('show_phone')->label('Mostrar telefone')->default(true),
                Toggle::make('show_whatsapp')->label('Mostrar WhatsApp')->default(false),
            ])
            ->columns(2);
    }
}
