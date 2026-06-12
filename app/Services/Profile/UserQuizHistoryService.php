<?php

namespace App\Services\Profile;

use App\Enums\SessionStatus;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\Quiz\QuizSessionClaimService;
use App\Services\Quiz\QuizSessionService;
use App\Support\LocaleConfig;
use App\Support\LocalizedNumbers;
use App\Support\MbtiContentCatalog;
use App\Support\MbtiTypePalette;
use App\Support\QuizResultViewData;
use App\Support\RebootProtocolLocalizedCopy;
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

        return $this->loadSessionsForUser($user)
            ->map(fn (QuizSession $session): array => $this->toRecord($session, $locale, $user))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function apiRecordsForUser(User $user, ?string $locale = null): Collection
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        return $this->loadSessionsForUser($user)
            ->map(fn (QuizSession $session): array => $this->toApiRecordFromSession($session, $locale, $user))
            ->values();
    }

    /**
     * @return Collection<int, QuizSession>
     */
    private function loadSessionsForUser(User $user): Collection
    {
        $this->claimService->claimForUser($user);
        $this->quizSessionService->abandonStaleEmptySessionsForUser($user);

        return QuizSession::query()
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
            ->get()
            ->filter(fn (QuizSession $session): bool => $session->quiz !== null)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function toApiRecordFromSession(QuizSession $session, string $locale, User $user): array
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

        $summary = $isInProgress
            ? $this->emptySummary((string) $session->quiz->slug)
            : $this->summarizeCompletedSession($session, $locale);

        return [
            'session_uuid' => $session->uuid,
            'quiz_name' => (string) $session->quiz->getTranslation('name', $locale, true),
            'quiz_slug' => (string) $session->quiz->slug,
            'type_code' => $summary['type_code'],
            'type_label' => $summary['type_label'],
            'result_title' => $summary['result_title'],
            'tagline' => $summary['tagline'],
            'palette' => $summary['palette'],
            'is_reboot_protocol' => $summary['is_reboot_protocol'],
            'is_in_progress' => $isInProgress,
            'progress_percent' => $progressPercent,
            'current_question' => $currentQuestion,
            'total_questions' => $totalQuestions,
            'completed_at_label' => $session->completed_at
                ? LocalizedNumbers::formatDate($session->completed_at, 'j M Y', $locale)
                : null,
            'started_at_label' => $session->started_at
                ? $session->started_at->locale($locale)->diffForHumans()
                : null,
            'status_label' => $isInProgress
                ? __('profile.status_in_progress', locale: $locale)
                : __('profile.status_completed', locale: $locale),
        ];
    }

    /**
     * @return array{
     *     type_code: ?string,
     *     type_label: ?string,
     *     result_title: ?string,
     *     tagline: ?string,
     *     palette: array{accent: string, soft: string, glow: string, group: string},
     *     is_reboot_protocol: bool,
     * }
     */
    private function summarizeCompletedSession(QuizSession $session, string $locale): array
    {
        $report = is_array($session->result?->free_report) ? $session->result->free_report : [];
        $isRebootProtocol = (string) $session->quiz->slug === RebootProtocolQuiz::SLUG
            || ($report['template'] ?? '') === 'reboot_protocol';

        if ($isRebootProtocol) {
            $localized = RebootProtocolLocalizedCopy::localizeReport($report, $locale);
            $content = RebootProtocolLocalizedCopy::localizeContent($report, $locale);

            return [
                'type_code' => null,
                'type_label' => null,
                'result_title' => (string) ($localized['title'] ?? ''),
                'tagline' => (string) ($content['tagline'] ?? $localized['score_tagline'] ?? $localized['summary'] ?? ''),
                'palette' => [
                    'accent' => '#34D399',
                    'soft' => 'rgba(52, 211, 153, 0.12)',
                    'glow' => 'rgba(52, 211, 153, 0.35)',
                    'group' => 'reboot',
                ],
                'is_reboot_protocol' => true,
            ];
        }

        $typeCode = strtolower((string) ($report['type_code'] ?? ''));
        $profile = $typeCode !== '' ? MbtiContentCatalog::profile($typeCode, $locale) : null;
        $typeLabel = is_array($profile) ? (string) ($profile['archetype'] ?? '') : null;
        $tagline = is_array($profile)
            ? (string) ($profile['tagline'] ?? '')
            : (string) ($report['summary'] ?? '');

        return [
            'type_code' => $typeCode !== '' ? strtoupper($typeCode) : null,
            'type_label' => $typeLabel !== '' ? $typeLabel : null,
            'result_title' => $typeLabel !== '' ? $typeLabel : (isset($report['title']) ? (string) $report['title'] : null),
            'tagline' => $tagline !== '' ? $tagline : null,
            'palette' => MbtiTypePalette::for($typeCode),
            'is_reboot_protocol' => false,
        ];
    }

    /**
     * @return array{
     *     type_code: ?string,
     *     type_label: ?string,
     *     result_title: ?string,
     *     tagline: ?string,
     *     palette: array{accent: string, soft: string, glow: string, group: string},
     *     is_reboot_protocol: bool,
     * }
     */
    private function emptySummary(string $quizSlug): array
    {
        $isRebootProtocol = $quizSlug === RebootProtocolQuiz::SLUG;

        return [
            'type_code' => null,
            'type_label' => null,
            'result_title' => null,
            'tagline' => null,
            'palette' => $isRebootProtocol
                ? [
                    'accent' => '#34D399',
                    'soft' => 'rgba(52, 211, 153, 0.12)',
                    'glow' => 'rgba(52, 211, 153, 0.35)',
                    'group' => 'reboot',
                ]
                : MbtiTypePalette::for(''),
            'is_reboot_protocol' => $isRebootProtocol,
        ];
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

        $routeParams = LocaleConfig::routeParameters(['uuid' => $session->uuid], $sessionLocale);

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
                ? route('quiz.session', $routeParams)
                : route('profile.test.show', $routeParams),
            'completed_at_label' => $session->completed_at
                ? LocalizedNumbers::formatDate($session->completed_at, 'j M Y', $sessionLocale)
                : null,
            'started_at_label' => $session->started_at
                ? $session->started_at->locale($sessionLocale)->diffForHumans()
                : null,
        ];
    }
}
