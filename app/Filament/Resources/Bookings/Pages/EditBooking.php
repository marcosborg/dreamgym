<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Services\BookingCancellationService;
use App\Services\BookingCreditService;
use App\Services\SandboxPaymentService;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record = Booking::query()->lockForUpdate()->findOrFail($record->id);
            $wasConfirmed = $record->status === Booking::STATUS_CONFIRMED;
            if ($wasConfirmed && in_array($record->paid_with, ['membership', 'credits'], true)) {
                foreach (['customer_email', 'paid_with', 'booking_type', 'starts_at', 'payment_status'] as $field) {
                    $old = $field === 'starts_at' ? $record->starts_at->format('Y-m-d H:i:s') : $record->$field;
                    $new = $field === 'starts_at' ? Carbon::parse($data[$field])->format('Y-m-d H:i:s') : ($data[$field] ?? null);
                    if ($new != $old) {
                        throw ValidationException::withMessages(['data.'.$field => 'Cancele esta reserva e crie outra para alterar os créditos ou o horário.']);
                    }
                }
                if (($data['status'] ?? null) === 'pending') {
                    throw ValidationException::withMessages(['data.status' => 'Uma reserva confirmada deve ser cancelada, não reposta como pendente.']);
                }
            }
            if ($record->status === Booking::STATUS_CANCELLED && ($data['status'] ?? null) !== $record->status) {
                throw ValidationException::withMessages(['data.status' => 'Crie uma nova reserva para voltar a reservar.']);
            }
            if (($data['status'] ?? null) === Booking::STATUS_CANCELLED && $wasConfirmed) {
                app(BookingCancellationService::class)->cancel($record);

                return $record->fresh();
            }
            $record->update($data);
            if (! $wasConfirmed && $record->status === Booking::STATUS_CONFIRMED) {
                app(BookingCreditService::class)->coverAdminBooking($record);
                if ($record->payment_status === 'paid') {
                    app(SandboxPaymentService::class)->confirmCoveredBooking($record);
                }
            }

            return $record->fresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
