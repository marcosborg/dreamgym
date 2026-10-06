<?php

namespace App\Http\Middleware;

use App\Services\ExpiredBookingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpireUnpaidBookingHolds
{
    public function handle(Request $request, Closure $next): Response
    {
        // Shared hosting runs cron every 15 minutes. Reads must not show stale holds
        // while waiting for the next background run.
        if ($request->isMethod('GET') && $request->is('book', 'account', 'checkout/*', 'admin', 'admin/*')) {
            app(ExpiredBookingService::class)->expire();
        }

        return $next($request);
    }
}
