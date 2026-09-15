<?php

namespace App\Services\Locks;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/** Read-only discovery: deliberately exposes no unlock or credential-writing APIs. */
class TtlockDiscoveryClient
{
    public function validateConfiguration(): void
    {
        if (! in_array(config('ttlock.base_url'), [
            'https://euapi.ttlock.com', 'https://api.ttlock.com', 'https://api.sciener.com',
        ], true)) {
            throw new RuntimeException('Configure TTLOCK_BASE_URL com o endpoint oficial da região da conta.');
        }

        foreach (['client_id', 'client_secret'] as $key) {
            if (! is_string(config("ttlock.$key")) || trim(config("ttlock.$key")) === '') {
                throw new RuntimeException('Configure TTLOCK_CLIENT_ID e TTLOCK_CLIENT_SECRET no ficheiro .env privado.');
            }
        }
    }

    public function discover(string $username, string $password): array
    {
        $this->validateConfiguration();
        if (trim($username) === '' || $password === '') {
            throw new RuntimeException('Introduza a conta e a palavra-passe da app TTLock.');
        }

        $auth = $this->request('/oauth2/token', [
            'client_id' => config('ttlock.client_id'),
            'client_secret' => config('ttlock.client_secret'),
            'username' => $username,
            'password' => md5($password),
        ]);
        if (! is_string($auth['access_token'] ?? null) || $auth['access_token'] === '') {
            throw new RuntimeException('Autenticação TTLock recusada. Confirme região, aplicação e conta da app.');
        }

        $locks = [];
        for ($page = 1; $page <= 100; $page++) {
            $response = $this->request('/v3/lock/list', [
                'clientId' => config('ttlock.client_id'),
                'accessToken' => $auth['access_token'],
                'pageNo' => $page,
                'pageSize' => 100,
                'date' => now()->getTimestampMs(),
            ]);
            if (! is_array($response['list'] ?? null)) {
                throw new RuntimeException('Resposta de descoberta TTLock inválida.');
            }
            foreach ($response['list'] as $lock) {
                if (! is_array($lock) || ! isset($lock['lockId'])) {
                    throw new RuntimeException('Resposta de descoberta TTLock inválida.');
                }
                // Never return lockData, MAC addresses, tokens or unfiltered API data.
                $locks[] = array_intersect_key($lock, array_flip([
                    'lockId', 'lockAlias', 'lockName', 'hasGateway', 'electricQuantity',
                ]));
            }
            if (count($response['list']) < 100) {
                return $locks;
            }
        }

        throw new RuntimeException('Limite de paginação atingido; descoberta incompleta.');
    }

    private function request(string $path, array $data): array
    {
        try {
            $response = Http::asForm()->connectTimeout(10)->timeout(30)
                ->withoutRedirecting()->post(config('ttlock.base_url').$path, $data);
        } catch (Throwable) {
            throw new RuntimeException('Não foi possível contactar a TTLock. Verifique rede e região.');
        }

        $json = $response->json();
        if (! $response->successful() || ! is_array($json)
            || isset($json['error']) || (isset($json['errcode']) && (int) $json['errcode'] !== 0)) {
            // Vendor errors and HTTP exceptions may contain credentials or request bodies.
            throw new RuntimeException('Pedido TTLock recusado. Confirme credenciais, região e permissões.');
        }

        return $json;
    }
}
