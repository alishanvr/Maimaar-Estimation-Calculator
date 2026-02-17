<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
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
