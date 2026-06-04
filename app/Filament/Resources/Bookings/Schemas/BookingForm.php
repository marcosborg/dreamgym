<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')->relationship('room', 'name')->required(),
                TextInput::make('customer_name')->required()->maxLength(120),
                TextInput::make('customer_email')->email()->required()->maxLength(160),
                TextInput::make('customer_phone')->maxLength(40),
                Select::make('locale')->options(['pt' => 'Portuguese', 'en' => 'English'])->default('pt')->required(),
                DateTimePicker::make('starts_at')->required()->seconds(false),
                DateTimePicker::make('ends_at')->required()->seconds(false)->after('starts_at'),
                Select::make('status')->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'])->required(),
                Select::make('payment_status')->options(['pending' => 'Pending', 'paid' => 'Paid', 'refunded' => 'Refunded'])->required(),
                TextInput::make('price_cents')->numeric()->required(),
                TextInput::make('currency')->required()->maxLength(3),
            ]);
    }
}
