<?php

namespace App\Providers;

use App\Services\Locks\IhrApiLockProvider;
use App\Services\Locks\LockProvider;
use App\Services\Locks\ManualIhrLockProvider;
use App\Services\Locks\SimulatedLockProvider;
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
                'ihr_api' => new IhrApiLockProvider,
                default => throw new InvalidArgumentException('LOCK_PROVIDER invalido. Usar simulated, manual_ihr ou ihr_api.'),
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
