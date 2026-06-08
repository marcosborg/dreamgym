<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('customer_name')->searchable(),
                TextColumn::make('customer_email')->searchable(),
                TextColumn::make('room.name'),
                TextColumn::make('booking_type')->badge(),
                TextColumn::make('seats_reserved')->label('Seats'),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('paid_with')->badge(),
                TextColumn::make('price_cents')->money('EUR', divideBy: 100),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled']),
                SelectFilter::make('payment_status')->options(['pending' => 'Pending', 'paid' => 'Paid']),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => $record->status !== Booking::STATUS_CANCELLED)
                    ->action(fn (Booking $record) => $record->update([
                        'status' => Booking::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
