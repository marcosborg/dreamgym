<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\CreditHistory;
use App\Services\SessionCreditService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record = User::lockForUpdate()->findOrFail($record->id);
            $reason = trim($data['credit_adjustment_reason'] ?? '');
            unset($data['credit_adjustment_reason']);
            $changes = [];
            foreach (['session', 'membership'] as $kind) {
                $changes[$kind] = (int) ($data[$kind.'_credits'] ?? $record->{$kind.'_credits'}) - $record->{$kind.'_credits'};
                if (($data[$kind.'_credits'] ?? 0) < 0 || ($changes[$kind] && ! $reason)) {
                    throw ValidationException::withMessages(['data.credit_adjustment_reason' => 'Indique o motivo e um saldo não negativo.']);
                }
            }
            $record->update($data);
            foreach ($changes as $kind => $delta) {
                CreditHistory::record($record, $kind, $delta, 'manual:'.$reason);
            }
            // Apply pack reductions now, before another purchase or refund can mask the adjustment.
            app(SessionCreditService::class)->expire($record);

            return $record->fresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => auth()->id() === $this->record->id),
        ];
    }
}
