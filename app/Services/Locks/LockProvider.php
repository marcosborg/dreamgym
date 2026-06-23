<?php

namespace App\Services\Locks;

use App\Models\AccessCode;

interface LockProvider
{
    public function provisionTemporaryPin(AccessCode $accessCode): AccessCode;
}
