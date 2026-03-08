<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\SystemController;
use App\Http\Controllers\AuthSecurity\RoleController;

// Aggregate all internal APIs here
require __DIR__ . '/users/internal-api.php';
require __DIR__ . '/gratitude/internal-api.php';

// Roles APIs
Route::get('roles', [RoleController::class, 'apiIndex']);
Route::get('roles/{role}', [RoleController::class, 'apiShow']);
Route::post('roles', [RoleController::class, 'apiStore']);
Route::put('roles/{role}', [RoleController::class, 'apiUpdate']);
Route::delete('roles/{role}', [RoleController::class, 'apiDestroy']);

// System Settings APIs
Route::get('settings', [SystemController::class, 'apiIndex']);
Route::post('settings', [SystemController::class, 'apiUpdate']);
Route::post('settings/upload', [SystemController::class, 'apiUploadImage']);
