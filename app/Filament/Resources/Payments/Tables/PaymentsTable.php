<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use App\Services\SandboxPaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('booking.customer_name')->label('Customer')->searchable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('product_type')->badge(),
                TextColumn::make('amount_cents')->money('EUR', divideBy: 100),
                TextColumn::make('status')->badge(),
                TextColumn::make('provider'),
                TextColumn::make('paid_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Marcar como pago')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record): bool => $record->status !== 'paid')
                    ->action(function (Payment $record): void {
                        if ($record->booking_id) {
                            app(SandboxPaymentService::class)->complete($record);
                        } else {
                            app(SandboxPaymentService::class)->completePurchase($record);
                        }

                        Notification::make()
                            ->title('Pagamento confirmado')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
