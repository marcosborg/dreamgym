<?php

namespace App\Services\Locks;

use App\Models\AccessCode;
use RuntimeException;

class IhrApiLockProvider implements LockProvider
{
    public function provisionTemporaryPin(AccessCode $accessCode): AccessCode
    {
        throw new RuntimeException('Provider ihr_api ainda nao esta ativo: faltam documentacao, endpoint e credenciais confirmadas da iHR Smart/L153.');
    }
}
