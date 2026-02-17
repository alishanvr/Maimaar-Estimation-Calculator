<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = file_exists(storage_path('app/installed'));
        $isInstallerRoute = $request->is('install', 'install/*');

        // Not installed — redirect everything (except installer routes) to /install
        if (! $isInstalled && ! $isInstallerRoute) {
            return redirect('/install');
        }

        // Already installed — redirect installer routes to /admin
        if ($isInstalled && $isInstallerRoute) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
