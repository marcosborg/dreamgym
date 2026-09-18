<?php

namespace App\Providers;

use App\Services\Locks\IhrApiLockProvider;
use App\Services\Locks\LockProvider;
use App\Services\Locks\ManualIhrLockProvider;
use App\Services\Locks\SimulatedLockProvider;
use App\Services\Locks\TtlockLockProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LockProvider::class, function () {
            return match (config('lock.provider', 'simulated')) {
                'simulated' => new SimulatedLockProvider,
                'manual_ihr' => new ManualIhrLockProvider,
                'ttlock' => app(TtlockLockProvider::class),
                'ihr_api' => new IhrApiLockProvider,
                default => throw new InvalidArgumentException('LOCK_PROVIDER invalido. Usar simulated, manual_ihr, ihr_api ou ttlock.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
