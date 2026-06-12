<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\QuizSessionStatePresenter;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function show(
        string $slug,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
    ): JsonResponse {
        $quiz = $quizSessionService->findActiveQuizBySlug($slug);

        return response()->json([
            'quiz' => $presenter->presentQuizMeta($quiz),
        ]);
    }
}
