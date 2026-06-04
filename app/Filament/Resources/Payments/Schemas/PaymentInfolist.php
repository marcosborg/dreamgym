<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference'),
                TextEntry::make('booking.customer_name'),
                TextEntry::make('amount_cents')->money('EUR', divideBy: 100),
                TextEntry::make('status')->badge(),
                TextEntry::make('provider'),
                TextEntry::make('paid_at')->dateTime('d/m/Y H:i'),
            ]);
    }
}
