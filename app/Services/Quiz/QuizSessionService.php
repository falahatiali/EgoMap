<?php

namespace App\Services\Quiz;

use App\Enums\ResultStatus;
use App\Enums\SessionStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResponse;
use App\Models\QuizResult;
use App\Models\QuizSession;
use App\Services\Quiz\Scoring\ScoringEngineFactory;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuizSessionService
{
    public function __construct(
        private readonly ScoringEngineFactory $scoringEngineFactory,
        private readonly QuizSessionClaimService $claimService,
    ) {}

    public function start(Quiz $quiz, ?string $guestToken = null): QuizSession
    {
        if (auth()->check()) {
            $guestToken = null;
        } else {
            $guestToken = $guestToken ?? $this->claimService->ensureGuestToken();
        }

        $session = QuizSession::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'guest_token' => $guestToken,
            'locale' => LocaleConfig::resolve(session('locale')),
            'status' => SessionStatus::InProgress,
            'current_sort_order' => 1,
            'started_at' => now(),
        ]);

        $this->claimService->rememberGuestSession($session);

        return $session;
    }

    public function findByUuid(string $uuid): QuizSession
    {
        $session = $this->findByUuidOrNull($uuid);

        if ($session === null) {
            throw new NotFoundHttpException('Quiz session not found.');
        }

        return $session;
    }

    public function findByUuidOrNull(string $uuid): ?QuizSession
    {
        return QuizSession::query()
            ->with(['quiz.questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('options')])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Resume an in-progress session when valid, otherwise start a fresh one.
     */
    public function resolveSessionForQuiz(Quiz $quiz, ?string $resumeUuid = null, ?string $guestToken = null): QuizSession
    {
        if ($resumeUuid !== null) {
            $existing = QuizSession::query()
                ->where('uuid', $resumeUuid)
                ->where('quiz_id', $quiz->id)
                ->first();

            if ($existing !== null && $existing->status === SessionStatus::InProgress) {
                return $existing->load(['quiz.questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('options')]);
            }
        }

        return $this->start($quiz, $guestToken);
    }

    public function findActiveQuizBySlug(string $slug): Quiz
    {
        return Quiz::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('options')])
            ->firstOrFail();
    }

    /**
     * @param  array{value: int|string|bool|list<int|string>}|int|string|bool  $value
     */
    public function saveAnswer(QuizSession $session, Question $question, array|int|string|bool $value): void
    {
        if ($session->status !== SessionStatus::InProgress) {
            return;
        }

        $normalized = is_array($value) ? $value : ['value' => $value];

        QuizResponse::query()->updateOrCreate(
            [
                'quiz_session_id' => $session->id,
                'question_id' => $question->id,
            ],
            [
                'value' => $normalized,
                'answered_at' => now(),
            ],
        );

        $session->update([
            'current_sort_order' => min($question->sort_order + 1, $this->lastQuestionSortOrder($session) + 1),
        ]);
    }

    public function complete(QuizSession $session): QuizResult
    {
        return DB::transaction(function () use ($session) {
            $session->refresh();
            $session->load(['responses.question.dimension', 'quiz.dimensions', 'quiz.outcomeProfiles']);

            $engine = $this->scoringEngineFactory->forQuiz($session->quiz);
            $scored = $engine->score($session);

            $session->update([
                'status' => SessionStatus::Completed,
                'completed_at' => now(),
            ]);

            return QuizResult::query()->updateOrCreate(
                ['quiz_session_id' => $session->id],
                [
                    'outcome_profile_id' => $scored['outcome_profile_id'],
                    'dimension_scores' => $scored['dimension_scores'],
                    'free_report' => $scored['free_report'],
                    'status' => ResultStatus::Ready,
                    'generated_at' => now(),
                ],
            );
        });
    }

    public function attachEmail(QuizSession $session, string $email): QuizSession
    {
        $session->update(['email' => $email]);

        return $session->refresh();
    }

    public function markEmailReportSent(QuizSession $session): void
    {
        $session->update(['email_report_sent_at' => now()]);
    }

    private function lastQuestionSortOrder(QuizSession $session): int
    {
        return (int) $session->quiz->questions()->max('sort_order');
    }
}
