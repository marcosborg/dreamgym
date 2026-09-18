<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Services\SandboxPaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $paid = ($data['status'] ?? 'pending') === 'paid';
            $data['status'] = 'pending';
            $payment = Payment::create($data);
            if ($paid) {
                $service = app(SandboxPaymentService::class);
                $payment->booking_id ? $service->complete($payment) : $service->completePurchase($payment);
            }

            return $payment->fresh();
        });
    }
}
