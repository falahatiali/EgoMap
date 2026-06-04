<?php

use Illuminate\Support\Facades\Route;
use Modules\GamificationEngine\Http\Controllers\GamificationApiController;

/*
|--------------------------------------------------------------------------
| Gamification JSON API (optional; prefer GamificationEngine via DI in PHP)
|--------------------------------------------------------------------------
|
| Requires Authorization (Sanctum bearer or session cookie on api stack).
| $context for service calls: user_id, guest_token, metadata
|
*/
Route::middleware(['auth:sanctum'])->prefix('v1/gamification')->name('gamification.')->group(function (): void {
    Route::get('wallet', [GamificationApiController::class, 'wallet'])->name('wallet');
    Route::get('transactions', [GamificationApiController::class, 'transactions'])->name('transactions');
    Route::post('dispatch', [GamificationApiController::class, 'dispatch'])->name('dispatch');
    Route::post('preview', [GamificationApiController::class, 'preview'])->name('preview');
    Route::get('shop', [GamificationApiController::class, 'shop'])->name('shop');
    Route::post('shop/{slug}/purchase', [GamificationApiController::class, 'purchaseShop'])->name('shop.purchase');
    Route::post('perks/{slug}/consume', [GamificationApiController::class, 'consumePerk'])->name('perks.consume');
});
