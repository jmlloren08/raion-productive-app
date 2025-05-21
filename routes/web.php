<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\ProductiveSyncController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Data endpoints
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/deals', [DealController::class, 'index']);
    Route::get('/deals/{id}', [DealController::class, 'show']);

    // Sync endpoints
    Route::prefix('productive')->group(function () {
        Route::get('/sync/status', [ProductiveSyncController::class, 'status'])->name('productive.sync.status');
        Route::post('/sync', [ProductiveSyncController::class, 'sync'])->name('productive.sync');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
