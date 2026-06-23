<?php

namespace App\Filament\Resources\AccessCodes\Pages;

use App\Filament\Resources\AccessCodes\AccessCodeResource;
use App\Models\AccessCode;
use App\Services\Locks\LockProvisioningService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAccessCode extends ViewRecord
{
    protected static string $resource = AccessCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
            EditAction::make(),
        ];
    }
}
