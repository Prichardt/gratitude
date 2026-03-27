<?php

use App\Http\Controllers\InternalApi\Gratitude\GratitudeBenefitController;
use App\Http\Controllers\InternalApi\Gratitude\GratitudeController;
use Illuminate\Support\Facades\Route;

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
        Route::delete('{gratitudeNumber}/earned/{id}', [GratitudeController::class, 'apiDeleteEarned'])->name('earned.destroy');
        Route::post('{gratitudeNumber}/bonus', [GratitudeController::class, 'apiAddBonus'])->name('bonus');
        Route::put('{gratitudeNumber}/bonus/{id}', [GratitudeController::class, 'apiUpdateBonus'])->name('bonus.update');
        Route::delete('{gratitudeNumber}/bonus/{id}', [GratitudeController::class, 'apiDeleteBonus'])->name('bonus.destroy');
        Route::post('{gratitudeNumber}/cancel', [GratitudeController::class, 'apiCancelPoints'])->name('cancel');
        Route::delete('{gratitudeNumber}/cancel/{id}', [GratitudeController::class, 'apiDeleteCancellation'])->name('cancel.destroy');
        Route::post('{gratitudeNumber}/expire', [GratitudeController::class, 'apiExpirePoints'])->name('expire');

        // Levels CRUD
        Route::get('levels', [\App\Http\Controllers\Gratitude\GratitudeLevelController::class, 'index'])->name('levels.index');
        Route::post('levels', [\App\Http\Controllers\Gratitude\GratitudeLevelController::class, 'store'])->name('levels.store');
        Route::put('levels/{level}', [\App\Http\Controllers\Gratitude\GratitudeLevelController::class, 'update'])->name('levels.update');
        Route::delete('levels/{level}', [\App\Http\Controllers\Gratitude\GratitudeLevelController::class, 'destroy'])->name('levels.destroy');

        // Benefits CRUD
        Route::get('benefits', [\App\Http\Controllers\Gratitude\GratitudeBenefitController::class, 'index'])->name('benefits.index');
        Route::post('benefits', [\App\Http\Controllers\Gratitude\GratitudeBenefitController::class, 'store'])->name('benefits.store');
        Route::put('benefits/{benefit}', [\App\Http\Controllers\Gratitude\GratitudeBenefitController::class, 'update'])->name('benefits.update');
        Route::delete('benefits/{benefit}', [\App\Http\Controllers\Gratitude\GratitudeBenefitController::class, 'destroy'])->name('benefits.destroy');

        // Program Level Benefits (Pivot)
        Route::get('program-benefits', [\App\Http\Controllers\Gratitude\ProgramLevelBenefitController::class, 'index'])->name('program-benefits.index');
        Route::put('program-benefits/{benefit}', [\App\Http\Controllers\Gratitude\ProgramLevelBenefitController::class, 'update'])->name('program-benefits.update');

        // Import Benefits
        Route::get('migrate-benefits/data', [GratitudeBenefitController::class, 'importBenefits'])->name('migrate-benefits.data');

        // Redemption CRUD
        Route::post('{gratitudeNumber}/redeem', [GratitudeController::class, 'apiAddRedeem'])->name('redeem');
        Route::get('{gratitudeNumber}/redeem/{id}', [GratitudeController::class, 'apiShowRedemption'])->name('redeem.show');
        Route::put('{gratitudeNumber}/redeem/{id}', [GratitudeController::class, 'apiUpdateRedemption'])->name('redeem.update');
        Route::delete('{gratitudeNumber}/redeem/{id}', [GratitudeController::class, 'apiDeleteRedemption'])->name('redeem.destroy');
    });
