<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')->relationship('room', 'name')->required(),
                Select::make('booking_type')->options(['single_hour' => 'Single hour', 'group_hour' => 'Group hour'])->required(),
                TextInput::make('seats_reserved')->numeric()->required(),
                TextInput::make('customer_name')->required()->maxLength(120),
                TextInput::make('customer_email')->email()->required()->maxLength(160),
                TextInput::make('customer_phone')->maxLength(40),
                Select::make('locale')->options(['pt' => 'Portuguese', 'en' => 'English'])->default('pt')->required(),
                Toggle::make('bringing_children')->label('Vai trazer crianças?'),
                DateTimePicker::make('children_responsibility_accepted_at')->label('Declaração crianças')->seconds(false),
                DateTimePicker::make('terms_accepted_at')->label('Termos aceites')->seconds(false),
                DateTimePicker::make('starts_at')->required()->seconds(false),
                DateTimePicker::make('ends_at')->required()->seconds(false)->after('starts_at'),
                Select::make('status')->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'])->required(),
                Select::make('payment_status')->options(['pending' => 'Pending', 'paid' => 'Paid', 'refunded' => 'Refunded'])->required(),
                Select::make('paid_with')->options(['payment' => 'Payment', 'credits' => 'Credits', 'membership' => 'Membership']),
                TextInput::make('price_cents')->numeric()->required(),
                TextInput::make('currency')->required()->maxLength(3),
            ]);
    }
}
