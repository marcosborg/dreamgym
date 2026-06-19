<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class SandboxDatabaseRefreshService
{
    public function refresh(Command $command, bool $keepDump = false, ?string $dumpPath = null): int
    {
        $production = config('database.connections.mysql_production');
        $sandbox = config('database.connections.mysql_sandbox');

        $this->guardSupportedConnection($production, 'production');
        $this->guardSupportedConnection($sandbox, 'sandbox');
        $this->guardDifferentDatabases($production, $sandbox);

        $dumpPath = $dumpPath ?: storage_path('app/database-backups/production-to-sandbox-'.now()->format('Ymd-His').'.sql');
        File::ensureDirectoryExists(dirname($dumpPath));

        $mysql = $this->binary('DB_MYSQL_BINARY', 'mysql');
        $mysqldump = $this->binary('DB_MYSQLDUMP_BINARY', 'mysqldump');

        $command->info('Creating production dump...');
        $this->runDump($mysqldump, $production, $dumpPath);

        $command->info('Ensuring sandbox database exists...');
        $this->runMysql($mysql, $this->withoutDatabase($sandbox), 'CREATE DATABASE IF NOT EXISTS `'.$this->escapeIdentifier($sandbox['database']).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

        $command->info('Resetting sandbox database...');
        $this->runMysql($mysql, $this->withoutDatabase($sandbox), 'DROP DATABASE `'.$this->escapeIdentifier($sandbox['database']).'`; CREATE DATABASE `'.$this->escapeIdentifier($sandbox['database']).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

        $command->info('Importing dump into sandbox...');
        $this->runImport($mysql, $sandbox, $dumpPath);

        if (! $keepDump) {
            File::delete($dumpPath);
        } else {
            $command->warn('Dump kept at: '.$dumpPath);
        }

        $command->info('Sandbox database refreshed from production.');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function guardSupportedConnection(array $connection, string $label): void
    {
        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException("The {$label} connection must use the mysql driver.");
        }

        foreach (['host', 'port', 'database', 'username'] as $key) {
            if (blank($connection[$key] ?? null)) {
                throw new RuntimeException("Missing {$label} database setting: {$key}.");
            }
        }
    }

    /**
     * @param array<string, mixed> $production
     * @param array<string, mixed> $sandbox
     */
    private function guardDifferentDatabases(array $production, array $sandbox): void
    {
        $productionKey = $this->connectionKey($production);
        $sandboxKey = $this->connectionKey($sandbox);

        if ($productionKey === $sandboxKey) {
            throw new RuntimeException('Production and sandbox database targets are identical. Refusing to continue.');
        }
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function connectionKey(array $connection): string
    {
        return implode('|', [
            $connection['host'] ?? '',
            $connection['port'] ?? '',
            $connection['database'] ?? '',
        ]);
    }

    private function binary(string $envKey, string $fallback): string
    {
        return (string) (env($envKey) ?: $fallback);
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function runDump(string $binary, array $connection, string $dumpPath): void
    {
        $process = new Process([
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            ...$this->connectionArgs($connection),
            (string) $connection['database'],
            '--result-file='.$dumpPath,
        ]);

        $this->runProcess($process);
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function runImport(string $binary, array $connection, string $dumpPath): void
    {
        $process = new Process([
            $binary,
            ...$this->connectionArgs($connection),
            (string) $connection['database'],
        ]);

        $handle = fopen($dumpPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open dump file for import: '.$dumpPath);
        }

        $process->setInput($handle);

        try {
            $this->runProcess($process);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function runMysql(string $binary, array $connection, string $sql): void
    {
        $process = new Process([
            $binary,
            ...$this->connectionArgs($connection),
            '--execute='.$sql,
        ]);

        $this->runProcess($process);
    }

    private function runProcess(Process $process): void
    {
        $process->setTimeout(null);
        $process->mustRun();
    }

    /**
     * @param array<string, mixed> $connection
     * @return array<int, string>
     */
    private function connectionArgs(array $connection): array
    {
        $args = [
            '--host='.(string) $connection['host'],
            '--port='.(string) $connection['port'],
            '--user='.(string) $connection['username'],
        ];

        if (filled($connection['password'] ?? null)) {
            $args[] = '--password='.(string) $connection['password'];
        }

        if (filled($connection['unix_socket'] ?? null)) {
            $args[] = '--socket='.(string) $connection['unix_socket'];
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $connection
     * @return array<string, mixed>
     */
    private function withoutDatabase(array $connection): array
    {
        return Arr::except($connection, ['database']);
    }

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }
}
