<?php

use Illuminate\Support\Facades\Route;
use Modules\AetherEngine\Http\Controllers\AetherApiController;

Route::middleware('auth:sanctum')->prefix('v1/aether')->name('aether.')->group(function (): void {
    Route::get('programs', [AetherApiController::class, 'programs'])->name('programs.index');
    Route::get('programs/{uuid}', [AetherApiController::class, 'programShow'])->name('programs.show');
    Route::post('programs/{uuid}/workout-days/{dayId}/sets/{setId}/toggle', [AetherApiController::class, 'toggleWorkoutSet'])
        ->name('programs.workout-sets.toggle');
});
