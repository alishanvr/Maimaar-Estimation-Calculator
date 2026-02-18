<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend SPA
|--------------------------------------------------------------------------
|
| The built Next.js static export lives in public/app/. The root route
| and the catch-all below serve the SPA's index.html so client-side
| routing handles all user-facing pages. The EnsureAppIsInstalled
| middleware still redirects to /install when needed.
|
*/
Route::get('/', function () {
    $spaIndex = public_path('app/index.html');

    if (file_exists($spaIndex)) {
        return response()->file($spaIndex);
    }

    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Installer Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based installation wizard. They are only
| accessible when the application has NOT yet been installed (no
| storage/app/installed flag). The EnsureAppIsInstalled middleware
| handles the redirect logic automatically.
|
*/
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallerController::class, 'showWelcome'])->name('welcome');
    Route::get('/requirements', [InstallerController::class, 'showRequirements'])->name('requirements');
    Route::get('/database', [InstallerController::class, 'showDatabase'])->name('database');
    Route::post('/database', [InstallerController::class, 'saveDatabase']);
    Route::get('/migrations', [InstallerController::class, 'showMigrations'])->name('migrations');
    Route::post('/migrations', [InstallerController::class, 'runMigrations']);
    Route::get('/application', [InstallerController::class, 'showApplication'])->name('application');
    Route::post('/application', [InstallerController::class, 'saveApplication']);
    Route::get('/mail', [InstallerController::class, 'showMail'])->name('mail');
    Route::post('/mail', [InstallerController::class, 'saveMail']);
    Route::get('/admin', [InstallerController::class, 'showAdmin'])->name('admin');
    Route::post('/admin', [InstallerController::class, 'saveAdmin']);
    Route::get('/finalize', [InstallerController::class, 'showFinalize'])->name('finalize');
    Route::post('/finalize', [InstallerController::class, 'runFinalize']);
});

/*
|--------------------------------------------------------------------------
| SPA Catch-All
|--------------------------------------------------------------------------
|
| Any route that doesn't match admin, install, api, sanctum, livewire,
| or the health-check endpoint is forwarded to the Next.js SPA. We first
| check for a route-specific pre-rendered page (e.g. public/app/login/
| index.html) so the browser receives the correct RSC payload for that
| route. If none exists we fall back to the root index.html and let
| client-side routing take over. This MUST be the last route.
|
*/
Route::get('/{any}', function (string $any) {
    $path = trim($any, '/');

    // Strip the "app/" prefix so route matching works with basePath: "/app"
    $spaPath = preg_replace('#^app/#', '', $path);

    // 1. Exact pre-rendered page (login, estimations, estimations/compare, etc.)
    $routeIndex = public_path('app/'.$spaPath.'/index.html');
    if (file_exists($routeIndex)) {
        return response()->file($routeIndex);
    }

    // 2. Dynamic route fallback — serve the placeholder "0" page so the
    //    browser gets the correct RSC payload and component tree.
    //    Without this, /estimations/41 would fall through to the root
    //    index.html which has the wrong RSC tree and causes a blank page.
    $dynamicRoutes = [
        '#^estimations/[^/]+$#' => 'app/estimations/0/index.html',
        '#^projects/[^/]+$#' => 'app/projects/0/index.html',
    ];

    foreach ($dynamicRoutes as $pattern => $placeholder) {
        if (preg_match($pattern, $spaPath)) {
            $file = public_path($placeholder);
            if (file_exists($file)) {
                return response()->file($file);
            }
        }
    }

    // 3. Root SPA shell for anything else
    $spaIndex = public_path('app/index.html');
    if (file_exists($spaIndex)) {
        return response()->file($spaIndex);
    }

    abort(404);
})->where('any', '^(?!admin|install|api|sanctum|livewire|up|_next|build).*$');
