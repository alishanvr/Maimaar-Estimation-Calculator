<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceFileSessionForInstaller
{
    /**
     * Force file-based sessions when the app is not yet installed.
     *
     * Before migrations run, the `sessions` table doesn't exist. Any web
     * request (not just /install routes) will crash when StartSession tries
     * to read from the database. This middleware switches to the file driver
     * for ALL requests when the installed flag is absent.
     *
     * Must be prepended to the web middleware group so it runs BEFORE
     * StartSession resolves its driver.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! file_exists(storage_path('app/installed'))) {
            config(['session.driver' => 'file']);
        }

        return $next($request);
    }
}
