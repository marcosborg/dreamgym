<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Services\MembershipReconciliationService;
use App\Services\ProductCatalog;
use App\Services\SandboxPaymentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record = Payment::query()->lockForUpdate()->findOrFail($record->id);
            // A confirmed payment's ownership and product cannot be silently reassigned.
            if ($record->status === 'paid') {
                foreach (['user_id', 'booking_id', 'product_type', 'status'] as $field) {
                    if (($data[$field] ?? null) != $record->$field) {
                        throw ValidationException::withMessages(['data.'.$field => 'Não é possível alterar este campo num pagamento confirmado.']);
                    }
                }
            }
            $complete = $record->status !== 'paid' && ($data['status'] ?? null) === 'paid';
            if ($complete) {
                $data['status'] = 'pending';
            }
            $record->update($data);
            if ($complete) {
                $service = app(SandboxPaymentService::class);
                $record->booking_id ? $service->complete($record) : $service->completePurchase($record);
            } elseif ($record->status === 'paid' && $record->product_type === ProductCatalog::MEMBERSHIP && $record->user) {
                app(MembershipReconciliationService::class)->reconcile($record->user);
            }

            return $record->fresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
