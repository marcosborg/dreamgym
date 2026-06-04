<?php

namespace App\Filament\Resources\BlackoutPeriods\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BlackoutPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')->relationship('room', 'name')->required(),
                TextInput::make('title')->required()->maxLength(160),
                DateTimePicker::make('starts_at')->required()->seconds(false),
                DateTimePicker::make('ends_at')->required()->seconds(false)->after('starts_at'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
