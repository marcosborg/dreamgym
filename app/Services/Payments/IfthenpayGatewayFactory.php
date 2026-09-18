<?php

namespace App\Services\Payments;

use GuzzleHttp\Client;
use Ifthenpay\PaymentGateway\IfthenpayGateway;

class IfthenpayGatewayFactory
{
    public function make(): IfthenpayGateway
    {
        $env = config('payments.ifthenpay.env', 'sandbox');
        $base = config('payments.ifthenpay');
        $active = $env === 'production'
            ? array_merge($base, array_filter($base['production'] ?? [], fn ($value) => $value !== null && $value !== ''))
            : $base;

        foreach (['backoffice_key', 'mb_key', 'mbway_key', 'callback_secret'] as $key) {
            if (empty($active[$key])) {
                throw new \RuntimeException('Configuração ifthenpay incompleta.');
            }
        }

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
        ], new Client(['timeout' => 20, 'connect_timeout' => 5, 'allow_redirects' => false]));
    }
}
