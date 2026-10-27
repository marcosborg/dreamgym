<?php

namespace App\Services;

use App\Models\CreditMovement;
use App\Models\User;

class CreditHistory
{
    public static function record(User $user, string $kind, int $amount, string $reason, ?int $bookingId = null): void
    {
        if (! $amount) {
            return;
        }
        CreditMovement::create(['user_id' => $user->id, 'kind' => $kind, 'amount' => $amount,
            'balance' => $user->fresh()->{$kind.'_credits'}, 'reason' => $reason, 'booking_id' => $bookingId,
            'actor_id' => auth()->id()]);
    }
}
