<?php

namespace App\Services\Locks;

use App\Models\TtlockConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class TtlockClient
{
    public function request(string $path, array $data = []): array
    {
        // Only APIs needed by booking access. Remote unlock is deliberately absent.
        if (! in_array($path, ['/v3/lock/list', '/v3/lock/detail', '/v3/gateway/listByLock', '/v3/gateway/list', '/v3/lock/listKeyboardPwd', '/v3/keyboardPwd/add', '/v3/keyboardPwd/delete'], true)) {
            throw new RuntimeException('Operação TTLock não suportada.');
        }
        $credentials = $this->credentials();

        return $this->post($credentials['base_url'], $path, array_merge($data, [
            'clientId' => $credentials['client_id'],
            'accessToken' => $credentials['access_token'],
            'date' => now()->getTimestampMs(),
        ]));
    }

    public function refreshIfNeeded(): void
    {
        $this->credentials();
    }

    private function credentials(): array
    {
        return Cache::lock('ttlock-token-refresh', 60)->block(10, function () {
            $connection = TtlockConnection::find(1);
            if (! $connection) {
                throw new RuntimeException('A conta TTLock não está configurada.');
            }
            $credentials = $connection->credentials;
            if (($credentials['expires_at'] ?? 0) <= now()->addMinutes(10)->timestamp) {
                $auth = $this->post($credentials['base_url'], '/oauth2/token', [
                    'client_id' => $credentials['client_id'],
                    'client_secret' => $credentials['client_secret'],
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $credentials['refresh_token'],
                ]);
                if (empty($auth['access_token']) || empty($auth['refresh_token']) || ($auth['expires_in'] ?? 0) < 1) {
                    throw new RuntimeException('Renovação TTLock inválida. Volte a autenticar a conta.');
                }
                $credentials = array_merge($credentials, [
                    'access_token' => $auth['access_token'],
                    'refresh_token' => $auth['refresh_token'],
                    'expires_at' => now()->timestamp + (int) $auth['expires_in'],
                ]);
                $connection->update(['credentials' => $credentials]);
            }

            return $credentials;
        });
    }

    private function post(string $baseUrl, string $path, array $data): array
    {
        if (! in_array($baseUrl, ['https://euapi.ttlock.com', 'https://api.ttlock.com', 'https://api.sciener.com'], true)) {
            throw new RuntimeException('Endpoint TTLock inválido.');
        }
        try {
            $response = Http::asForm()->connectTimeout(5)->timeout(15)->withoutRedirecting()->post($baseUrl.$path, $data);
            $json = $response->json();
        } catch (Throwable) {
            // Never chain HTTP exceptions: they may include tokens or request bodies.
            throw new RuntimeException('Sem resposta da TTLock. O estado será reconciliado antes de tentar novamente.');
        }
        if (! $response->successful() || ! is_array($json) || isset($json['error']) || (isset($json['errcode']) && (int) $json['errcode'] !== 0)) {
            $code = is_array($json) && is_numeric($json['errcode'] ?? null) ? (int) $json['errcode'] : 0;
            throw new RuntimeException('Pedido TTLock recusado (código '.$code.').');
        }

        return $json;
    }
}
