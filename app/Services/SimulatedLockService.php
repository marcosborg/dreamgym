<?php

namespace App\Services;

use App\Models\AccessCode;
use App\Services\Locks\SimulatedLockProvider;

class SimulatedLockService
{
    public function provision(AccessCode $accessCode): AccessCode
    {
        return app(SimulatedLockProvider::class)->provisionTemporaryPin($accessCode);
    }
}
