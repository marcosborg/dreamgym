<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                    ->helperText('Campanha de lançamento: adesão até 31/12/2026, sem limite de sócios. 40 € por 30 dias, com 3 dias de tolerância para renovar. Uma interrupção faz perder o desconto.')
                    ->default(false),
                Toggle::make('is_admin')
                    ->label('Admin')
                    ->helperText('Allows this user to access the admin panel.')
                    ->default(false),
                TextInput::make('credit_adjustment_reason')->label('Motivo da alteração dos créditos')->helperText('Obrigatório quando alterar o saldo. Fica registado no histórico; não envia email.')->maxLength(200),
                TextInput::make('session_credits')
                    ->label('Créditos de packs')
                    ->numeric()
                    ->default(0),
                TextInput::make('membership_credits')
                    ->label('Créditos da mensalidade')
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('membership_expires_at')
                    ->label('Validade dos créditos da mensalidade')
                    ->seconds(false)
                    ->helperText('Os créditos da mensalidade só permitem reservar antes desta data. Ao atribuir créditos manualmente, indique a validade acordada. Para transferências de créditos avulsos, use Créditos de packs.'),
            ]);
    }
}
