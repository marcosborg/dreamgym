<?php

use App\Services\Payments\IfthenpayGatewayFactory;
use App\Services\SandboxDatabaseRefreshService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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

Artisan::command('ifthenpay:register-webhooks {--url= : Public callback URL, defaults to route(ifthenpay.callback)} {--method=all : all, multibanco, or mbway}', function (IfthenpayGatewayFactory $factory) {
    $url = $this->option('url') ?: route('ifthenpay.callback');
    $method = $this->option('method');
    $gateway = $factory->make();
    // The provider returned an empty anti-phishing placeholder in live callbacks.
    // Register the configured key explicitly; never print the returned URL.
    $params = ['apk' => rawurlencode((string) config('payments.ifthenpay.callback_secret'))];

    if (! str_starts_with($url, 'https://')) {
        $this->warn('The callback URL should be public HTTPS before using this in production.');
    }

    if (in_array($method, ['all', 'multibanco'], true)) {
        $gateway->multibancoDynamic()->registerWebhook($url, $params);
        $this->info('Multibanco webhook registered.');
    }

    if (in_array($method, ['all', 'mbway'], true)) {
        $gateway->mbway()->registerWebhook($url, $params);
        $this->info('MB WAY webhook registered.');
    }

    if (! in_array($method, ['all', 'multibanco', 'mbway'], true)) {
        $this->error('Invalid method. Use all, multibanco, or mbway.');

        return 1;
    }

    return 0;
})->purpose('Register ifthenpay Multibanco/MB WAY callback URLs');
