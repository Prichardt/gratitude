<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    require __DIR__ . '/users/web.php';
    require __DIR__ . '/gratitude/web.php';

    // Internal API

    Route::prefix('internal-api')->name('internal-api.')->group(function () {
        require __DIR__ . '/users/internal-api.php';
        require __DIR__ . '/gratitude/internal-api.php';
    });
});

require __DIR__ . '/settings.php';

// External API (Sanctum token auth)
Route::prefix('api/v1')->name('api.')->middleware(['auth:sanctum'])->group(function () {
    require __DIR__ . '/gratitude/external-api.php';
});
