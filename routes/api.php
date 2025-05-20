<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\ProductiveSyncController;
use Illuminate\Support\Facades\Route;

// Productive.io API Routes
Route::prefix('api')->group(function () {
    // Data endpoints
    Route::apiResource('companies', CompanyController::class)->only(['index', 'show']);
    Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);

    // Sync endpoints
    Route::prefix('productive')->group(function () {
        Route::get('/sync/status', [ProductiveSyncController::class, 'status'])->name('productive.sync.status');
        Route::post('/sync', [ProductiveSyncController::class, 'sync'])->name('productive.sync');
    });
});
