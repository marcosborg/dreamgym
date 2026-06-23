<?php

return [
    'provider' => env('LOCK_PROVIDER', 'simulated'),

    'pin_length' => (int) env('LOCK_PIN_LENGTH', 6),

    'access_start_buffer_minutes' => (int) env('LOCK_ACCESS_START_BUFFER_MINUTES', 5),

    'access_end_buffer_minutes' => (int) env('LOCK_ACCESS_END_BUFFER_MINUTES', 5),
];
