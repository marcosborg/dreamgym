<?php

namespace App\Filament\Resources\AccessCodes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccessCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_id')->relationship('booking', 'customer_name')->required(),
                TextInput::make('code')->required()->maxLength(6),
                DateTimePicker::make('valid_from')->required()->seconds(false),
                DateTimePicker::make('valid_until')->required()->seconds(false),
                Select::make('provision_status')->options(['pending' => 'Pending', 'provisioned' => 'Provisioned', 'failed' => 'Failed'])->required(),
            ]);
    }
}
