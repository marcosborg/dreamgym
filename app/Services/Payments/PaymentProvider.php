<?php

namespace App\Services\Payments;

class PaymentProvider
{
    public function isIfthenpay(): bool
    {
        return config('payments.provider') === 'ifthenpay';
    }
}
