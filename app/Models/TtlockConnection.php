<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtlockConnection extends Model
{
    protected $fillable = ['credentials'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array'];
    }
}
