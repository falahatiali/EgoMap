<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Quiz\ApiQuizGuestTokenService;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\QuizSessionStatePresenter;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function entry(
        Request $request,
        string $slug,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
    ): JsonResponse {
        $validated = $request->validate([
            'resume_uuid' => ['nullable', 'uuid'],
        ]);

        $quiz = $quizSessionService->findActiveQuizBySlug($slug);
        $guestToken = $guestTokens->resolveFromRequest($request);
        $user = $request->user('sanctum');
        $issuedGuestToken = null;

        if ($user === null && $guestToken === null) {
            $guestToken = $guestTokens->issue();
            $issuedGuestToken = $guestToken;
        }

        $entry = $quizSessionService->resolveApiEntry(
            $quiz,
            $user,
            $guestToken,
            $validated['resume_uuid'] ?? null,
        );

        $payload = $presenter->presentEntry(
            $entry,
            LocaleConfig::resolve($request->header('Accept-Language')),
        );

        if ($issuedGuestToken !== null) {
            $payload['guest_token'] = $issuedGuestToken;
        }

        return response()->json($payload);
    }
}
