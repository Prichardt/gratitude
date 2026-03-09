<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InternalApi\Gratitude\GratitudeController;

// Gratitude APIs


Route::name('gratitude.')->prefix('gratitude/')
    ->group(function () {
        Route::get('migrate-data', [GratitudeController::class, 'import'])->name('import');
        Route::get('/', [GratitudeController::class, 'apiIndex'])->name('index');
        Route::get('overview', [GratitudeController::class, 'apiOverview'])->name('overview');
        Route::get('reserve', [GratitudeController::class, 'apiReserve'])->name('reserve');
        Route::get('history', [GratitudeController::class, 'apiHistory'])->name('history');
        Route::get('account/show/{gratitudeNumber}', [GratitudeController::class, 'apiShow'])->name('account.show');
        Route::post('{gratitudeNumber}/earned', [GratitudeController::class, 'apiAddEarned'])->name('earned');
        Route::put('{gratitudeNumber}/earned/{id}', [GratitudeController::class, 'apiUpdateEarned'])->name('earned.update');
        Route::post('{gratitudeNumber}/bonus', [GratitudeController::class, 'apiAddBonus'])->name('bonus');
        Route::post('{gratitudeNumber}/cancel', [GratitudeController::class, 'apiCancelPoints'])->name('cancel');
        Route::post('{gratitudeNumber}/expire', [GratitudeController::class, 'apiExpirePoints'])->name('expire');
    });
