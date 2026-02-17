<?php

use App\Http\Controllers\Api\AppSettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignConfigurationController;
use App\Http\Controllers\Api\EstimationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Public endpoints (no auth required)
Route::get('/app-settings', AppSettingsController::class)->name('app-settings');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    // Authentication & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);

    // Design configurations (dropdown data)
    Route::get('/design-configurations', [DesignConfigurationController::class, 'index'])
        ->name('design-configurations.index');
    Route::get('/freight-codes', [DesignConfigurationController::class, 'freightCodes'])
        ->name('freight-codes.index');
    Route::get('/paint-systems', [DesignConfigurationController::class, 'paintSystems'])
        ->name('paint-systems.index');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/export/csv', [ReportController::class, 'exportCsv'])
            ->name('export.csv')
            ->middleware('throttle:exports');
        Route::get('/export/pdf', [ReportController::class, 'exportPdf'])
            ->name('export.pdf')
            ->middleware('throttle:exports');
    });

    // Projects CRUD
    Route::apiResource('projects', ProjectController::class);
    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('/buildings', [ProjectController::class, 'buildings'])->name('buildings');
        Route::post('/buildings', [ProjectController::class, 'addBuilding'])->name('buildings.add');
        Route::delete('/buildings/{estimation}', [ProjectController::class, 'removeBuilding'])->name('buildings.remove');
        Route::post('/buildings/{estimation}/duplicate', [ProjectController::class, 'duplicateBuilding'])->name('buildings.duplicate');
        Route::get('/history', [ProjectController::class, 'history'])->name('history');
    });

    // Collection-level estimation actions (BEFORE apiResource to avoid route conflicts)
    Route::post('/estimations/compare', [EstimationController::class, 'compare'])
        ->name('estimations.compare');
    Route::post('/estimations/bulk-export', [EstimationController::class, 'bulkExport'])
        ->name('estimations.bulk-export')
        ->middleware('throttle:exports');

    // Estimations CRUD
    Route::apiResource('estimations', EstimationController::class);

    // Estimation instance actions & sheet data
    Route::prefix('estimations/{estimation}')->name('estimations.')->group(function () {
        Route::post('/clone', [EstimationController::class, 'clone'])->name('clone');
        Route::post('/revision', [EstimationController::class, 'createRevision'])->name('revision');
        Route::get('/revisions', [EstimationController::class, 'revisions'])->name('revisions');
        Route::post('/finalize', [EstimationController::class, 'finalize'])->name('finalize');
        Route::post('/unlock', [EstimationController::class, 'unlock'])->name('unlock');

        Route::post('/calculate', [EstimationController::class, 'calculate'])
            ->name('calculate')
            ->middleware('throttle:calculate');

        Route::get('/detail', [EstimationController::class, 'detail'])->name('detail');
        Route::get('/recap', [EstimationController::class, 'recap'])->name('recap');
        Route::get('/fcpbs', [EstimationController::class, 'fcpbs'])->name('fcpbs');
        Route::get('/sal', [EstimationController::class, 'sal'])->name('sal');
        Route::get('/boq', [EstimationController::class, 'boq'])->name('boq');
        Route::get('/jaf', [EstimationController::class, 'jaf'])->name('jaf');
        Route::get('/rawmat', [EstimationController::class, 'rawmat'])->name('rawmat');

        Route::middleware('throttle:exports')->group(function () {
            Route::get('/export/recap', [EstimationController::class, 'exportRecap'])->name('export.recap');
            Route::get('/export/detail', [EstimationController::class, 'exportDetail'])->name('export.detail');
            Route::get('/export/fcpbs', [EstimationController::class, 'exportFcpbs'])->name('export.fcpbs');
            Route::get('/export/sal', [EstimationController::class, 'exportSal'])->name('export.sal');
            Route::get('/export/boq', [EstimationController::class, 'exportBoq'])->name('export.boq');
            Route::get('/export/jaf', [EstimationController::class, 'exportJaf'])->name('export.jaf');
            Route::get('/export/rawmat', [EstimationController::class, 'exportRawmat'])->name('export.rawmat');
        });
    });
});
