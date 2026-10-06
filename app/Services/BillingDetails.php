<?php

namespace App\Services;

use App\Models\Payment;

class BillingDetails
{
    public static function rules(): array
    {
        return [
            'billing_nif' => ['nullable', 'string', 'regex:/^[0-9]{9}$/'],
            'billing_address' => ['required_with:billing_nif', 'nullable', 'string', 'max:255'],
            'billing_postal_code' => ['required_with:billing_nif', 'nullable', 'string', 'max:20'],
            'billing_city' => ['required_with:billing_nif', 'nullable', 'string', 'max:120'],
        ];
    }

    public static function attributes(Payment $payment, array $data): array
    {
        if ($payment->status === 'paid') {
            return [];
        }

        $result = [];
        foreach (array_keys(self::rules()) as $field) {
            $result[$field] = empty($data['billing_nif']) ? null : ($data[$field] ?? null);
        }

        return $result;
    }
}
