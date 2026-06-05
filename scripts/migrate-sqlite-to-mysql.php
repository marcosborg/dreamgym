<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourcePath = $argv[1] ?? database_path('database.sqlite');
$shouldWrite = in_array('--write', $argv, true);

if (! is_file($sourcePath)) {
    fwrite(STDERR, "SQLite source not found: {$sourcePath}" . PHP_EOL);
    exit(1);
}

if (config('database.default') !== 'mysql') {
    fwrite(STDERR, 'Set DB_CONNECTION=mysql in .env before running this script.' . PHP_EOL);
    exit(1);
}

$sqlite = new PDO('sqlite:' . $sourcePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = [
    'users',
    'rooms',
    'opening_hours',
    'blackout_periods',
    'bookings',
    'access_codes',
    'payments',
];

if (! $shouldWrite) {
    echo 'Dry run. Add --write to migrate data into MySQL.' . PHP_EOL;
}

Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();

$mysql = DB::connection('mysql');
$mysql->statement('SET FOREIGN_KEY_CHECKS=0');

try {
    if ($shouldWrite) {
        foreach (array_reverse($tables) as $table) {
            $mysql->table($table)->truncate();
        }
    }

    foreach ($tables as $table) {
        $rows = $sqlite->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);

        echo sprintf('%s: %d rows', $table, count($rows)) . PHP_EOL;

        if ($shouldWrite && count($rows) > 0) {
            foreach (array_chunk($rows, 100) as $chunk) {
                $mysql->table($table)->insert($chunk);
            }
        }
    }
} finally {
    $mysql->statement('SET FOREIGN_KEY_CHECKS=1');
}

echo $shouldWrite ? 'SQLite data migrated to MySQL.' . PHP_EOL : 'Dry run complete.' . PHP_EOL;
