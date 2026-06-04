<?php

namespace App\Filament\Resources\AccessCodes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccessCodeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('booking.customer_name'),
                TextEntry::make('valid_from')->dateTime('d/m/Y H:i'),
                TextEntry::make('valid_until')->dateTime('d/m/Y H:i'),
                TextEntry::make('provision_status')->badge(),
                TextEntry::make('lock_response_log')->columnSpanFull(),
            ]);
    }
}
