<?php

namespace App\Http\Controllers\Api;

use App\Enums\QuestionType;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuizSession;
use App\Services\Quiz\ApiQuizGuestTokenService;
use App\Services\Quiz\QuizResultDeliveryService;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Services\Quiz\QuizSessionStatePresenter;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuizSessionController extends Controller
{
    public function store(
        Request $request,
        string $slug,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
        QuizSessionClaimService $claimService,
    ): JsonResponse {
        $validated = $request->validate([
            'resume_uuid' => ['nullable', 'uuid'],
        ]);

        $quiz = $quizSessionService->findActiveQuizBySlug($slug);
        $issuedGuestToken = null;
        $guestToken = $guestTokens->resolveFromRequest($request);
        $user = $request->user('sanctum');

        if ($user !== null) {
            $claimService->claimForUser($user, $guestToken);
            $quizSessionService->abandonStaleEmptySessionsForUser($user, $quiz->id);

            $resumable = $quizSessionService->findResumableInProgressForUser($quiz, $user);

            if ($resumable !== null && empty($validated['resume_uuid'])) {
                return response()->json($presenter->present($resumable));
            }
        }

        if (! empty($validated['resume_uuid'])) {
            $existing = QuizSession::query()
                ->where('uuid', $validated['resume_uuid'])
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($existing !== null) {
                $presenter->authorizeSessionAccess($existing, $guestToken);

                if ($existing->status === SessionStatus::Completed) {
                    return response()->json($presenter->present($existing));
                }
            }

            $session = $quizSessionService->resolveSessionForQuiz(
                $quiz,
                $validated['resume_uuid'],
                $guestToken,
            );
        } else {
            if ($user === null && $guestToken === null) {
                $guestToken = $guestTokens->issue();
                $issuedGuestToken = $guestToken;
            }

            $session = $quizSessionService->start($quiz, $guestToken);
        }

        if ($user !== null && $session->user_id === null) {
            $claimService->claimSession($session, $user);
            $session->refresh();
        }

        $payload = $presenter->present($session);

        if ($issuedGuestToken !== null) {
            $payload['guest_token'] = $issuedGuestToken;
        }

        return response()->json($payload, 201);
    }

    public function show(
        Request $request,
        string $uuid,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
    ): JsonResponse {
        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));

        return response()->json($presenter->present($session));
    }

    public function answer(
        Request $request,
        string $uuid,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
        RebootProtocolFlow $flow,
    ): JsonResponse {
        $validated = $request->validate([
            'value' => ['required'],
        ]);

        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));

        if ($session->status !== SessionStatus::InProgress) {
            throw ValidationException::withMessages([
                'session' => ['This quiz session is no longer in progress.'],
            ]);
        }

        $question = $this->resolveCurrentQuestion($session);

        if ($question === null) {
            throw ValidationException::withMessages([
                'session' => ['No active question found for this session.'],
            ]);
        }

        $value = $validated['value'];

        if ($question->type === QuestionType::MultipleChoice) {
            if (! is_array($value)) {
                throw ValidationException::withMessages([
                    'value' => ['Multiple-choice answers must be an array.'],
                ]);
            }

            $quizSessionService->saveAnswer($session, $question, ['value' => array_values($value)]);
        } else {
            if (is_array($value)) {
                throw ValidationException::withMessages([
                    'value' => ['Single-choice answers must be a string.'],
                ]);
            }

            $quizSessionService->saveAnswer($session, $question, (string) $value);
        }

        $session->refresh();

        if ($flow->isRebootQuiz($session->quiz->slug) && $flow->shouldPromptSafety($session, $question)) {
            $flow->markSafetyPending($session);
            $session->refresh();
        } else {
            $this->advanceOrComplete($session, $quizSessionService);
            $session->refresh();
        }

        return response()->json($presenter->present($session));
    }

    public function safetyAnswer(
        Request $request,
        string $uuid,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
        RebootProtocolFlow $flow,
    ): JsonResponse {
        $validated = $request->validate([
            'value' => ['required', 'integer', 'between:1,4'],
        ]);

        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));

        if ($session->status !== SessionStatus::InProgress) {
            throw ValidationException::withMessages([
                'session' => ['This quiz session is no longer in progress.'],
            ]);
        }

        $result = $flow->recordSafetyAnswer($session, (int) $validated['value']);
        $flow->clearSafetyPrompt($session);
        $session->refresh();

        if (! $result['crisis']) {
            $this->advanceOrComplete($session, $quizSessionService);
            $session->refresh();
        }

        return response()->json($presenter->present($session));
    }

    public function back(
        Request $request,
        string $uuid,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
        RebootProtocolFlow $flow,
    ): JsonResponse {
        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));

        if ($session->status !== SessionStatus::InProgress || $session->current_sort_order <= 1) {
            return response()->json($presenter->present($session));
        }

        if ($flow->isSafetyPending($session)) {
            $flow->clearSafetyPrompt($session);
        }

        $session->update([
            'current_sort_order' => $session->current_sort_order - 1,
        ]);

        $session->refresh();

        return response()->json($presenter->present($session));
    }

    public function result(
        Request $request,
        string $uuid,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
    ): JsonResponse {
        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));

        if ($session->status === SessionStatus::InProgress) {
            $session->load(['responses.question', 'quiz.dimensions', 'quiz.outcomeProfiles']);

            if ($session->responses->count() >= $session->quiz->questions()->where('is_active', true)->count()) {
                $quizSessionService->complete($session);
                $session->refresh();
            }
        }

        if ($session->result === null && $session->status === SessionStatus::Completed) {
            $quizSessionService->complete($session->fresh(['responses.question']));
            $session->refresh();
        }

        return response()->json($presenter->present($session));
    }

    public function sendReport(
        Request $request,
        string $uuid,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
        QuizResultDeliveryService $delivery,
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));

        if ($session->status !== SessionStatus::Completed) {
            if ($session->result === null) {
                $quizSessionService->complete($session->fresh(['responses.question']));
                $session->refresh();
            }
        }

        $delivery->queueEmailReport($session, $validated['email']);
        $session->refresh();

        return response()->json([
            'screen' => 'result',
            'session' => [
                'uuid' => $session->uuid,
                'status' => $session->status->value,
                'quiz_slug' => $session->quiz->slug,
                'locale' => $session->locale,
            ],
            'result' => $presenter->presentResult($session),
        ]);
    }

    public function resetAfterCrisis(
        Request $request,
        string $uuid,
        QuizSessionService $quizSessionService,
        QuizSessionStatePresenter $presenter,
        ApiQuizGuestTokenService $guestTokens,
    ): JsonResponse {
        $session = $presenter->findAuthorizedSession($uuid, $guestTokens->resolveFromRequest($request));
        $guestToken = $guestTokens->resolveFromRequest($request);
        $user = $request->user('sanctum');

        if ($user === null && $guestToken === null) {
            $guestToken = $guestTokens->issue();
        }

        $newSession = $quizSessionService->start($session->quiz, $guestToken);

        $payload = $presenter->present($newSession);

        if ($guestToken !== null && $session->guest_token !== $guestToken) {
            $payload['guest_token'] = $guestToken;
        }

        return response()->json($payload, 201);
    }

    private function resolveCurrentQuestion(QuizSession $session): ?Question
    {
        return Question::query()
            ->where('quiz_id', $session->quiz_id)
            ->where('sort_order', (int) $session->current_sort_order)
            ->where('is_active', true)
            ->with('options')
            ->first();
    }

    private function advanceOrComplete(QuizSession $session, QuizSessionService $quizSessionService): void
    {
        $session->loadMissing(['quiz.questions']);

        $lastSort = (int) $session->quiz->questions->max('sort_order');

        if ($session->current_sort_order > $lastSort) {
            $quizSessionService->complete($session);
        }
    }
}
