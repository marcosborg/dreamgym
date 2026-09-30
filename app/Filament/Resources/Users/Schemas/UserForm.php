<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(40),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                Toggle::make('is_founder')
                    ->label('Sócio fundador')
                    ->helperText('Mensalidade de 40 € com 3 dias de tolerância. Uma interrupção no histórico de pagamentos faz perder o desconto. Identificar apenas os sócios aprovados pelo David (20 inicialmente; até 30 se decidir alargar).')
                    ->default(false),
                Toggle::make('is_admin')
                    ->label('Admin')
                    ->helperText('Allows this user to access the admin panel.')
                    ->default(false),
                TextInput::make('session_credits')
                    ->label('Créditos de packs')
                    ->numeric()
                    ->default(0),
                TextInput::make('membership_credits')
                    ->label('Créditos da mensalidade')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
