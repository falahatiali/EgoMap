<?php

use Illuminate\Support\Facades\Route;
use Modules\VirtueEngine\Http\Controllers\VirtueApiController;

Route::middleware('auth:sanctum')
    ->prefix('v1/virtue')
    ->name('virtue.')
    ->group(function (): void {
        Route::get('habits', [VirtueApiController::class, 'habits'])->name('habits.index');
        Route::post('habits/analyze', [VirtueApiController::class, 'analyzeHabit'])->name('habits.analyze');

        Route::get('routines', [VirtueApiController::class, 'routines'])->name('routines.index');
        Route::post('routines', [VirtueApiController::class, 'startRoutine'])->name('routines.store');
        Route::get('routines/{routineId}', [VirtueApiController::class, 'routineProgress'])->name('routines.show');
        Route::post('routines/{routineId}/success', [VirtueApiController::class, 'logSuccess'])->name('routines.success');
        Route::post('routines/{routineId}/slip', [VirtueApiController::class, 'logSlip'])->name('routines.slip');
        Route::post('routines/{routineId}/complete', [VirtueApiController::class, 'completeRoutine'])->name('routines.complete');
    });
