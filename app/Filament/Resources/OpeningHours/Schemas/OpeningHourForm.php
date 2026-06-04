<?php

namespace App\Filament\Resources\OpeningHours\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OpeningHourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')->relationship('room', 'name')->required(),
                Select::make('weekday')->options([
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ])->required(),
                TextInput::make('opens_at')->type('time')->required(),
                TextInput::make('closes_at')->type('time')->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
