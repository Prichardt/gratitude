<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthSecurity\UserController;
use App\Http\Controllers\AuthSecurity\RoleController;
use App\Http\Controllers\AuthSecurity\PermissionController;
use App\Http\Controllers\AuthSecurity\ApplicationKeyController;
use App\Http\Controllers\GratitudeController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::resource('permissions', PermissionController::class)->except(['show']);
    Route::get('/application-keys', [ApplicationKeyController::class, 'index'])->name('application-keys.index');
    Route::get('/gratitude', [GratitudeController::class, 'index'])->name('gratitude.index');
    Route::resource('users', UserController::class)->except(['show']);
});
