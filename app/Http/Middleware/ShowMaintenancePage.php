<?php

namespace App\Http\Middleware;

use App\Services\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowMaintenancePage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $settings = app(SiteSettings::class);

        if (! $settings->maintenanceEnabled()) {
            return $next($request);
        }

        if ($settings->canBypassMaintenance($request->ip())) {
            return $next($request);
        }

        return response()
            ->view('maintenance', status: 503)
            ->header('Retry-After', '3600');
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->is('admin*')
            || $request->is('livewire*')
            || $request->is('build*')
            || $request->is('brand*')
            || $request->is('favicon.ico')
            || $request->is('robots.txt')
            || $request->is('up');
    }
}
