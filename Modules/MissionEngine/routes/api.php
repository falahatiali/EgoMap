<?php

use Illuminate\Support\Facades\Route;
use Modules\MissionEngine\Http\Controllers\MissionEngineController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('missionengines', MissionEngineController::class)->names('missionengine');
});
