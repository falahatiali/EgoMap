<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/bootstrap', [BootstrapController::class, 'show'])->name('bootstrap');

    Route::get('/quizzes/{slug}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{slug}/sessions', [QuizSessionController::class, 'store'])->name('quizzes.sessions.store');

    Route::prefix('quiz-sessions/{uuid}')->name('quiz-sessions.')->group(function (): void {
        Route::get('/', [QuizSessionController::class, 'show'])->name('show');
        Route::post('/answers', [QuizSessionController::class, 'answer'])->name('answers');
        Route::post('/safety-answer', [QuizSessionController::class, 'safetyAnswer'])->name('safety-answer');
        Route::post('/back', [QuizSessionController::class, 'back'])->name('back');
        Route::get('/result', [QuizSessionController::class, 'result'])->name('result');
        Route::post('/send-report', [QuizSessionController::class, 'sendReport'])->name('send-report');
        Route::post('/reset-after-crisis', [QuizSessionController::class, 'resetAfterCrisis'])->name('reset-after-crisis');
    });

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify-email');
        Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('resend-verification');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
});
