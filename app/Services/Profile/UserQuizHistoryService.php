<?php

namespace App\Services\Profile;

use App\Enums\SessionStatus;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Support\LocaleConfig;
use App\Support\LocalizedNumbers;
use App\Support\MbtiTypePalette;
use App\Support\QuizResultViewData;
use App\Support\RebootProtocolQuiz;
use Illuminate\Support\Collection;

class UserQuizHistoryService
{
    public function __construct(
        private readonly QuizSessionClaimService $claimService,
        private readonly QuizSessionService $quizSessionService,
    ) {}

    /**
     * Load every quiz attempt that belongs to the user, with display-ready metadata.
     *
     * @return Collection<int, array{
     *     session: QuizSession,
     *     quiz_name: string,
     *     quiz_slug: string,
     *     type_code: ?string,
     *     type_label: ?string,
     *     result_title: ?string,
     *     tagline: ?string,
     *     palette: array{accent: string, soft: string, glow: string, group: string},
     *     is_reboot_protocol: bool,
     *     is_in_progress: bool,
     *     progress_percent: int,
     *     current_question: int,
     *     total_questions: int,
     *     detail_url: string,
     *     completed_at_label: ?string,
     *     started_at_label: ?string,
     * }>
     */
    public function recordsForUser(User $user, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        $this->claimService->claimForUser($user);
        $this->quizSessionService->abandonStaleEmptySessionsForUser($user);

        $sessions = QuizSession::query()
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id);

                if ($user->email !== null && $user->email !== '') {
                    $query->orWhere('email', $user->email);
                }
            })
            ->whereIn('status', [SessionStatus::InProgress, SessionStatus::Completed])
            ->with([
                'quiz' => fn ($query) => $query->withCount([
                    'questions' => fn ($questions) => $questions->where('is_active', true),
                ]),
                'result.outcomeProfile',
            ])
            ->latest('updated_at')
            ->get();

        return $sessions
            ->filter(fn (QuizSession $session): bool => $session->quiz !== null)
            ->map(fn (QuizSession $session): array => $this->toRecord($session, $locale, $user))
            ->values();
    }

    /**
     * @return array{
     *     session: QuizSession,
     *     quiz_name: string,
     *     quiz_slug: string,
     *     type_code: ?string,
     *     type_label: ?string,
     *     result_title: ?string,
     *     tagline: ?string,
     *     palette: array{accent: string, soft: string, glow: string, group: string},
     *     is_reboot_protocol: bool,
     *     is_in_progress: bool,
     *     progress_percent: int,
     *     current_question: int,
     *     total_questions: int,
     *     detail_url: string,
     *     completed_at_label: ?string,
     *     started_at_label: ?string,
     * }
     */
    private function toRecord(QuizSession $session, string $locale, User $user): array
    {
        if ($session->user_id === null) {
            $this->claimService->claimSession($session, $user);
            $session->refresh();
        }

        $isInProgress = $session->status === SessionStatus::InProgress;
        $totalQuestions = max((int) ($session->quiz->questions_count ?? 0), 1);
        $currentQuestion = min((int) ($session->current_sort_order ?? 1), $totalQuestions);
        $progressPercent = $isInProgress
            ? (int) round(($currentQuestion / $totalQuestions) * 100)
            : 100;

        $sessionLocale = LocaleConfig::resolve($locale);
        $report = [];
        $tagline = null;
        $typeLabel = null;
        $palette = MbtiTypePalette::for('');
        $isRebootProtocol = (string) $session->quiz->slug === RebootProtocolQuiz::SLUG;

        if (! $isInProgress) {
            $resultData = QuizResultViewData::fromSession($session, $sessionLocale);
            $report = $resultData['report'];
            $tagline = (string) ($resultData['content']['tagline'] ?? ($report['summary'] ?? ''));
            $typeLabel = (string) ($resultData['content']['type_label'] ?? ($report['title'] ?? ''));
            $palette = $resultData['palette'];
        }

        $typeCode = isset($report['type_code']) ? (string) $report['type_code'] : null;
        $resultTitle = $typeLabel !== null && $typeLabel !== ''
            ? $typeLabel
            : (isset($report['title']) ? (string) $report['title'] : null);

        if (
            $typeCode !== null
            && $typeCode !== ''
            && ($report['template'] ?? '') !== 'reboot_protocol'
            && ! $isRebootProtocol
        ) {
            $palette = MbtiTypePalette::for(strtolower($typeCode));
        }

        return [
            'session' => $session,
            'quiz_name' => (string) $session->quiz->getTranslation('name', $sessionLocale, true),
            'quiz_slug' => (string) $session->quiz->slug,
            'type_code' => $typeCode,
            'type_label' => $typeLabel !== '' ? $typeLabel : null,
            'result_title' => $resultTitle,
            'tagline' => $tagline !== '' ? $tagline : null,
            'palette' => $palette,
            'is_reboot_protocol' => $isRebootProtocol || (($report['template'] ?? '') === 'reboot_protocol'),
            'is_in_progress' => $isInProgress,
            'progress_percent' => $progressPercent,
            'current_question' => $currentQuestion,
            'total_questions' => $totalQuestions,
            'detail_url' => $isInProgress
                ? route('quiz.session', ['uuid' => $session->uuid])
                : route('profile.test.show', ['uuid' => $session->uuid]),
            'completed_at_label' => $session->completed_at
                ? LocalizedNumbers::formatDate($session->completed_at, 'j M Y', $sessionLocale)
                : null,
            'started_at_label' => $session->started_at
                ? $session->started_at->locale($sessionLocale)->diffForHumans()
                : null,
        ];
    }
}
