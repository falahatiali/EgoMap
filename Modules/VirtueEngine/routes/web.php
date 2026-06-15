<?php

use Illuminate\Support\Facades\Route;
use Modules\VirtueEngine\Http\Controllers\VirtueEngineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('virtueengines', VirtueEngineController::class)->names('virtueengine');
});
