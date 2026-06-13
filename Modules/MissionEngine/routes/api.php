<?php

use Illuminate\Support\Facades\Route;
use Modules\MissionEngine\Http\Controllers\MissionApiController;

Route::prefix('v1/missions')->name('missions.')->group(function (): void {
    Route::get('/', [MissionApiController::class, 'catalog'])->name('catalog');
    Route::get('/{slug}', [MissionApiController::class, 'show'])->name('show');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/{slug}/enroll', [MissionApiController::class, 'enroll'])->name('enroll');
    });
});

Route::middleware('auth:sanctum')->prefix('v1/mission-enrollments')->name('mission-enrollments.')->group(function (): void {
    Route::get('/', [MissionApiController::class, 'enrollments'])->name('index');
    Route::get('/{uuid}', [MissionApiController::class, 'enrollmentShow'])->name('show');
    Route::patch('/{uuid}/fields', [MissionApiController::class, 'updateFields'])->name('fields.update');
    Route::get('/{uuid}/daily-reports', [MissionApiController::class, 'showDailyReport'])->name('daily-reports.show');
    Route::post('/{uuid}/daily-reports', [MissionApiController::class, 'saveDailyReport'])->name('daily-reports.store');
    Route::post('/{uuid}/supplements/products', [MissionApiController::class, 'addSupplementProduct'])->name('supplements.products.store');
    Route::post('/{uuid}/supplements/intakes', [MissionApiController::class, 'logSupplementIntake'])->name('supplements.intakes.store');
    Route::post('/{uuid}/programs/generate', [MissionApiController::class, 'generateProgram'])->name('programs.generate');
    Route::get('/{uuid}/calibration/defaults', [MissionApiController::class, 'calibrationDefaults'])->name('calibration.defaults');
    Route::post('/{uuid}/calibration/complete', [MissionApiController::class, 'calibrationComplete'])->name('calibration.complete');
    Route::post('/{uuid}/calibration/regenerate', [MissionApiController::class, 'calibrationRegenerate'])->name('calibration.regenerate');
});
