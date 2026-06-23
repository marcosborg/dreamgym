<?php

namespace App\Filament\Resources\AccessCodes\Tables;

use App\Models\AccessCode;
use App\Services\Locks\LockProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable(),
                TextColumn::make('booking.customer_name')->label('Customer')->searchable(),
                TextColumn::make('booking.starts_at')->label('Booking')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('valid_from')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('valid_until')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('provision_status')->badge(),
                TextColumn::make('provisioned_at')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('markManuallyConfigured')
                    ->label('Marcar como configurado')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AccessCode $record): bool => $record->provision_status !== AccessCode::PROVISIONED)
                    ->action(function (AccessCode $record): void {
                        app(LockProvisioningService::class)->markManuallyConfigured($record);

                        Notification::make()
                            ->title('PIN marcado como configurado')
                            ->success()
                            ->send();
                    }),
                Action::make('retryProvisioning')
                    ->label('Tentar provisionar novamente')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (AccessCode $record): void {
                        $updated = app(LockProvisioningService::class)->provision($record);
                        $notification = Notification::make()
                            ->title($updated->provision_status === AccessCode::FAILED ? 'Provisionamento falhou' : 'Provisionamento atualizado');

                        $updated->provision_status === AccessCode::FAILED
                            ? $notification->danger()->send()
                            : $notification->success()->send();
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
