<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\GhostModeController;
use App\Http\Controllers\Api\MissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/bootstrap', [BootstrapController::class, 'show'])->name('bootstrap');

    Route::get('/quizzes/{slug}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::get('/quizzes/{slug}/entry', [QuizController::class, 'entry'])->name('quizzes.entry');
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

    Route::prefix('ghost-mode')->name('ghost-mode.')->group(function (): void {
        Route::get('/', [GhostModeController::class, 'show'])->name('show');
        Route::post('/protocol', [GhostModeController::class, 'startProtocol'])->name('protocol.start');
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

    Route::middleware('auth:sanctum')->prefix('profile')->name('profile.')->group(function (): void {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
    });

    Route::middleware('auth:sanctum')->prefix('missions')->name('missions.')->group(function (): void {
        Route::get('/', [MissionController::class, 'index'])->name('index');
        Route::get('/enrollments/{uuid}', [MissionController::class, 'workspace'])->name('workspace');
        Route::post('/{slug}/enroll', [MissionController::class, 'enroll'])->name('enroll');
        Route::get('/{slug}', [MissionController::class, 'show'])->name('show');
    });
});
