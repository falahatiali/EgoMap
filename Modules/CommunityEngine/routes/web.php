<?php

use Illuminate\Support\Facades\Route;
use Modules\CommunityEngine\Http\Controllers\CommunityEngineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('communityengines', CommunityEngineController::class)->names('communityengine');
});
