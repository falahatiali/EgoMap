<?php

namespace App\Services\Quiz;

use App\Enums\ResultStatus;
use App\Enums\SessionStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResponse;
use App\Models\QuizResult;
use App\Models\QuizSession;
use App\Models\User;
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
            'locale' => LocaleConfig::active(),
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
            ->with([
                'quiz.questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('options'),
                'responses',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findInProgressForUser(Quiz $quiz, User $user): ?QuizSession
    {
        return QuizSession::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', SessionStatus::InProgress)
            ->latest('updated_at')
            ->first();
    }

    public function findResumableInProgressForUser(Quiz $quiz, User $user): ?QuizSession
    {
        $sessions = QuizSession::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', SessionStatus::InProgress)
            ->withCount('responses')
            ->latest('updated_at')
            ->get();

        foreach ($sessions as $session) {
            if ($this->hasMeaningfulProgress($session)) {
                return $session->load([
                    'quiz.questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('options'),
                ]);
            }
        }

        return null;
    }

    public function hasMeaningfulProgress(QuizSession $session): bool
    {
        if (($session->responses_count ?? $session->responses()->count()) > 0) {
            return true;
        }

        return ($session->current_sort_order ?? 1) > 1;
    }

    public function abandonStaleEmptySessionsForUser(User $user, ?int $quizId = null): int
    {
        return QuizSession::query()
            ->where('user_id', $user->id)
            ->when($quizId !== null, fn ($query) => $query->where('quiz_id', $quizId))
            ->where('status', SessionStatus::InProgress)
            ->whereDoesntHave('responses')
            ->where('current_sort_order', '<=', 1)
            ->update(['status' => SessionStatus::Abandoned]);
    }

    /**
     * Decide how a signed-in user should enter a quiz.
     *
     * @return array{action: 'resume'|'show_previous'|'start_fresh', session: QuizSession|null}
     */
    public function resolveAuthenticatedEntry(Quiz $quiz, User $user): array
    {
        $this->claimService->claimForUser($user);
        $this->abandonStaleEmptySessionsForUser($user, $quiz->id);

        $resumable = $this->findResumableInProgressForUser($quiz, $user);

        if ($resumable !== null) {
            return [
                'action' => 'resume',
                'session' => $resumable,
            ];
        }

        $completed = $this->findLatestCompletedForUser($quiz, $user);

        if ($completed !== null) {
            return [
                'action' => 'show_previous',
                'session' => $completed,
            ];
        }

        return [
            'action' => 'start_fresh',
            'session' => null,
        ];
    }

    public function findLatestCompletedForUser(Quiz $quiz, User $user): ?QuizSession
    {
        return QuizSession::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', SessionStatus::Completed)
            ->with(['result.outcomeProfile', 'quiz'])
            ->latest('completed_at')
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

    public function findActiveQuizBySlugOrNull(string $slug): ?Quiz
    {
        return Quiz::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['questions' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('options')])
            ->first();
    }

    public function findActiveQuizBySlug(string $slug): Quiz
    {
        $quiz = $this->findActiveQuizBySlugOrNull($slug);

        if ($quiz === null) {
            throw new NotFoundHttpException('Quiz not found.');
        }

        return $quiz;
    }

    /**
     * @param  array{value: int|string|bool|list<int|string>}|int|string|bool  $value
     */
    public function saveAnswer(
        QuizSession $session,
        Question $question,
        array|int|string|bool $value,
        bool $advance = true,
    ): void {
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

        if (! $advance) {
            return;
        }

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
