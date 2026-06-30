<?php

namespace App\Services\Payments;

use Ifthenpay\PaymentGateway\IfthenpayGateway;

class IfthenpayGatewayFactory
{
    public function make(): IfthenpayGateway
    {
        $env = config('payments.ifthenpay.env', 'sandbox');
        $base = config('payments.ifthenpay');
        $active = $env === 'production'
            ? array_merge($base, $base['production'] ?? [])
            : $base;

        return new IfthenpayGateway([
            'backofficeKey' => $active['backoffice_key'],
            'antiPhishingKey' => $base['callback_secret'],
            'language' => app()->getLocale() ?: 'pt',
            'mbway' => [
                'key' => $active['mbway_key'],
                'minutesToExpire' => $base['mbway_minutes_to_expire'],
            ],
            'multibancoDynamic' => [
                'key' => $active['mb_key'],
                'daysToExpire' => $base['multibanco_days_to_expire'],
            ],
        ]);
    }
}
