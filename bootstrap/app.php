<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->trustProxies(at: '*');
        $middleware->web(
            prepend: [\App\Http\Middleware\ForceFileSessionForInstaller::class],
            append: [
                \App\Http\Middleware\EnsureAppIsInstalled::class,
                \App\Http\Middleware\HandleInertiaRequests::class,
            ],
        );
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('v2/*')) {
                return '/v2/login';
            }

            return null;
        });
        $middleware->redirectUsersTo(function ($request) {
            if ($request->is('v2/*')) {
                return '/v2/dashboard';
            }

            return '/admin';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        ]);
    })->create();
