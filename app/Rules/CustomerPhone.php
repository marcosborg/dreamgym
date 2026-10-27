<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomerPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = strlen(preg_replace('/\D/', '', (string) $value));
        if (! preg_match('/^\+?[0-9 () .-]+$/', (string) $value) || $digits < 7 || $digits > 15) {
            $fail(__('site.invalid_phone'));
        }
    }
}
