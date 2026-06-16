<?php

use Illuminate\Support\Facades\Route;
use Modules\CommunityEngine\Http\Controllers\CommunityApiController;

// Public endpoints (feed + comments readable without auth)
Route::prefix('v1/community')->name('community.')->group(function (): void {
    Route::get('posts', [CommunityApiController::class, 'feed'])->name('posts.index');
    Route::get('posts/{post}/comments', [CommunityApiController::class, 'comments'])->name('posts.comments.index');

    // Auth-only actions
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('posts', [CommunityApiController::class, 'store'])->name('posts.store');
        Route::delete('posts/{post}', [CommunityApiController::class, 'destroy'])->name('posts.destroy');
        Route::post('posts/{post}/react', [CommunityApiController::class, 'react'])->name('posts.react');

        Route::post('posts/{post}/comments', [CommunityApiController::class, 'storeComment'])->name('posts.comments.store');
        Route::delete('comments/{comment}', [CommunityApiController::class, 'destroyComment'])->name('comments.destroy');
        Route::post('comments/{comment}/react', [CommunityApiController::class, 'reactToComment'])->name('comments.react');
    });
});
