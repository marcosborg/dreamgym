<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_id')->relationship('booking', 'customer_name'),
                Select::make('user_id')->relationship('user', 'name'),
                Select::make('product_type')->options([
                    'single_hour' => 'Single hour',
                    'group_hour' => 'Group hour',
                    'session_pack' => 'Session pack',
                    'membership' => 'Membership',
                ])->required(),
                TextInput::make('metadata.credits')
                    ->label('Créditos a atribuir')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Preencher para packs e mensalidades (por exemplo, 6, 12 ou 30).'),
                TextInput::make('metadata.days')
                    ->label('Dias de validade')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Preencher para mensalidades (normalmente 30).'),
                TextInput::make('provider')->required(),
                TextInput::make('reference')->required(),
                TextInput::make('amount_cents')->numeric()->required(),
                TextInput::make('currency')->required()->maxLength(3),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed'])
                    ->default('pending')
                    ->helperText('Novos pagamentos são guardados como pendentes. Use “Marcar como pago” na lista para aplicar créditos e validade.')
                    ->required(),
                DateTimePicker::make('paid_at')->seconds(false),
            ]);
    }
}
