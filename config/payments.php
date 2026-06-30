<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'sandbox'),

    'ifthenpay' => [
        'env' => env('IFTHENPAY_ENV', 'sandbox'),
        'backoffice_key' => env('IFTHENPAY_BACKOFFICE_KEY'),
        'mb_key' => env('IFTHENPAY_MB_KEY'),
        'mbway_key' => env('IFTHENPAY_MBWAY_KEY'),
        'payshop_key' => env('IFTHENPAY_PAYSHOP_KEY'),
        'gateway_key' => env('IFTHENPAY_GATEWAY_KEY'),
        'entity' => env('IFTHENPAY_ENTITY'),
        'subentity' => env('IFTHENPAY_SUBENTITY'),
        'callback_secret' => env('IFTHENPAY_CALLBACK_SECRET'),
        'mbway_minutes_to_expire' => (int) env('IFTHENPAY_MBWAY_MINUTES_TO_EXPIRE', 4),
        'multibanco_days_to_expire' => (int) env('IFTHENPAY_MULTIBANCO_DAYS_TO_EXPIRE', 3),

        'production' => [
            'backoffice_key' => env('IFTHENPAY_PRODUCTION_BACKOFFICE_KEY'),
            'mb_key' => env('IFTHENPAY_PRODUCTION_MB_KEY'),
            'mbway_key' => env('IFTHENPAY_PRODUCTION_MBWAY_KEY'),
            'payshop_key' => env('IFTHENPAY_PRODUCTION_PAYSHOP_KEY'),
            'gateway_key' => env('IFTHENPAY_PRODUCTION_GATEWAY_KEY'),
            'entity' => env('IFTHENPAY_PRODUCTION_ENTITY'),
            'subentity' => env('IFTHENPAY_PRODUCTION_SUBENTITY'),
        ],
    ],
];
