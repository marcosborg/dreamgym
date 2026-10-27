<?php

namespace App\Services;

use App\Mail\NewCustomer;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerRegistrationMail
{
    public static function send(User $user): void
    {
        try {
            Mail::to('info@dreamgym.pt')->send(new NewCustomer($user));
        } catch (\Throwable $e) {
            Log::error('Falha no aviso de novo cliente.', ['user_id' => $user->id]);
        }
    }
}
