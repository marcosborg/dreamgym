<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\SandboxDatabaseRefreshService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:refresh-sandbox {--yes : Skip confirmation} {--keep-dump : Keep the SQL dump after import} {--dump-path= : Custom dump file path}', function (SandboxDatabaseRefreshService $refresh) {
    $production = config('database.connections.mysql_production.database');
    $sandbox = config('database.connections.mysql_sandbox.database');

    $this->warn("This will replace the sandbox database [{$sandbox}] with a copy of production [{$production}].");

    if (! $this->option('yes') && ! $this->confirm('Continue?')) {
        $this->info('Cancelled.');

        return 0;
    }

    return $refresh->refresh(
        command: $this,
        keepDump: (bool) $this->option('keep-dump'),
        dumpPath: $this->option('dump-path') ?: null,
    );
})->purpose('Refresh the sandbox database from a safe production dump');
