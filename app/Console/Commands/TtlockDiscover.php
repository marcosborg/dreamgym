<?php

namespace App\Console\Commands;

use App\Services\Locks\TtlockDiscoveryClient;
use Illuminate\Console\Command;
use RuntimeException;

class TtlockDiscover extends Command
{
    protected $signature = 'ttlock:discover';

    protected $description = 'Autenticar e listar fechaduras TTLock sem operar hardware ou alterar reservas';

    public function handle(TtlockDiscoveryClient $client): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Este diagnóstico está disponível apenas no ambiente local.');

            return self::FAILURE;
        }

        try {
            $client->validateConfiguration();
            if (! $this->input->isInteractive()) {
                $this->error('Execute interativamente para introduzir credenciais em campos ocultos.');

                return self::FAILURE;
            }
            $username = $this->secret('Conta da app TTLock (não a conta de developer)');
            $password = $this->secret('Palavra-passe da app TTLock');
            $locks = $client->discover((string) $username, (string) $password);
            unset($username, $password);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['ID', 'Nome', 'Gateway associado', 'Bateria %'], array_map(fn ($lock) => [
            $lock['lockId'],
            preg_replace('/[\x00-\x1F\x7F]/u', '', (string) ($lock['lockAlias'] ?? $lock['lockName'] ?? '')),
            isset($lock['hasGateway']) ? ((int) $lock['hasGateway'] === 1 ? 'Sim' : 'Não') : 'Desconhecido',
            $lock['electricQuantity'] ?? 'Desconhecida',
        ], $locks));
        $this->info(count($locks).' fechadura(s). Nenhum comando físico enviado; tokens não guardados.');

        return self::SUCCESS;
    }
}
