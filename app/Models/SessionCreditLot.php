<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionCreditLot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'credits_granted' => 'integer', 'remaining_credits' => 'integer'];
    }
}
