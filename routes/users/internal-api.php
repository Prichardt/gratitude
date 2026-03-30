<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthSecurity\UserController;
use App\Http\Controllers\AuthSecurity\ApplicationKeyController;

Route::get('users', [UserController::class, 'apiIndex']);
Route::get('users/{user}', [UserController::class, 'apiShow']);
Route::post('users', [UserController::class, 'apiStore']);
Route::put('users/{user}', [UserController::class, 'apiUpdate']);
Route::delete('users/{user}', [UserController::class, 'apiDestroy']);

Route::get('application-keys', [ApplicationKeyController::class, 'apiIndex']);
Route::get('application-keys/{application_key}', [ApplicationKeyController::class, 'apiShow']);
Route::post('application-keys', [ApplicationKeyController::class, 'apiStore']);
Route::put('application-keys/{application_key}', [ApplicationKeyController::class, 'apiUpdate']);
Route::patch('application-keys/{application_key}/toggle-status', [ApplicationKeyController::class, 'apiToggleStatus']);
Route::post('application-keys/{application_key}/regenerate-token', [ApplicationKeyController::class, 'apiRegenerateToken']);
Route::delete('application-keys/{application_key}', [ApplicationKeyController::class, 'apiDestroy']);
