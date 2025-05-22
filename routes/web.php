<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\TimeEntryVersionController;
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
    
    // Time Entries Page
    Route::get('/time-entries', function () {
        return Inertia::render('time-entries');
    })->name('time-entries');

    // Data endpoints
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/deals', [DealController::class, 'index']);
    Route::get('/deals/{id}', [DealController::class, 'show']);
    Route::get('/time-entries', [TimeEntryController::class, 'index']);
    Route::get('/time-entries/{id}', [TimeEntryController::class, 'show']);
    Route::get('/time-entry-versions', [TimeEntryVersionController::class, 'index']);
    Route::get('/time-entry-versions/{id}', [TimeEntryVersionController::class, 'show']);
    Route::get('/time-entries/{id}/history', [TimeEntryVersionController::class, 'history']);

    // Sync endpoints
    Route::prefix('productive')->group(function () {
        Route::get('/sync/status', [ProductiveSyncController::class, 'status'])->name('productive.sync.status');
        Route::post('/sync', [ProductiveSyncController::class, 'sync'])->name('productive.sync');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
