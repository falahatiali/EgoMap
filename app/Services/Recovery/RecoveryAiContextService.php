<?php

namespace App\Services\Recovery;

use App\Enums\SessionStatus;
use App\Models\GhostModeEvent;
use App\Models\NoContactProtocol;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use App\Services\Quiz\RebootProtocol\RebootProtocolFlow;
use App\Support\RebootProtocolQuiz;
use Modules\GamificationEngine\Services\GamificationEngine;

readonly class RecoveryAiContextService
{
    public function __construct(
        private RebootProtocolFlow $rebootFlow,
        private NoContactTimerService $timerService,
        private GamificationEngine $gamification,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forQuizSession(QuizSession $session): array
    {
        $session->loadMissing(['result', 'quiz']);

        $answers = $this->rebootFlow->answersByKey($session);
        $freeReport = is_array($session->result?->free_report) ? $session->result->free_report : [];

        return [
            'locale' => $session->locale,
            'quiz_slug' => $session->quiz?->slug,
            'answers' => $answers,
            'rule_report' => $this->compactRuleReport($freeReport),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forGhostMode(?User $user = null, ?NoContactProtocol $protocol = null): array
    {
        $user ??= auth()->user();
        $protocol ??= $this->timerService->findActiveProtocol();

        $context = [
            'locale' => app()->getLocale(),
            'protocol' => $protocol === null ? null : [
                'duration_days' => $protocol->duration_days,
                'slip_count' => $protocol->slip_count,
                'elapsed_days' => $this->elapsedDays($protocol),
                'progress_percent' => $this->progressPercent($protocol),
            ],
            'recent_events' => [],
            'assessment' => null,
        ];

        if ($protocol !== null) {
            $context['recent_events'] = GhostModeEvent::query()
                ->where('no_contact_protocol_id', $protocol->id)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (GhostModeEvent $event): array => [
                    'type' => $event->type->value,
                    'trigger' => $event->trigger,
                    'created_at' => $event->created_at?->toIso8601String(),
                ])
                ->all();
        }

        $context['gamification'] = [
            'wallet' => $this->gamification->walletFor($user, auth()->check() ? null : request()->cookie('egomap_guest')),
            'recent_transactions' => $this->gamification->recentTransactions($user, 3),
        ];

        if ($user !== null) {
            $session = QuizSession::query()
                ->where('user_id', $user->id)
                ->whereHas('quiz', fn ($query) => $query->where('slug', RebootProtocolQuiz::SLUG))
                ->where('status', SessionStatus::Completed)
                ->with('result')
                ->latest('completed_at')
                ->first();

            if ($session !== null) {
                $context['assessment'] = $this->forQuizSession($session);
            }
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function forBlackhole(string $message, ?User $user = null, ?NoContactProtocol $protocol = null): array
    {
        return array_merge(
            $this->forGhostMode($user, $protocol),
            ['draft_message' => $message],
        );
    }

    /**
     * @param  array<string, mixed>  $freeReport
     * @return array<string, mixed>
     */
    private function compactRuleReport(array $freeReport): array
    {
        if (($freeReport['template'] ?? '') !== 'reboot_protocol') {
            return [];
        }

        return [
            'stability_score' => $freeReport['stability_score'] ?? null,
            'type_code' => $freeReport['type_code'] ?? null,
            'phase' => $freeReport['phase'] ?? null,
            'main_risk' => $freeReport['main_risk'] ?? null,
            'detected_patterns' => $freeReport['detected_patterns'] ?? [],
            'dimensions' => $freeReport['dimensions'] ?? [],
            'emergency' => $freeReport['emergency'] ?? false,
        ];
    }

    private function elapsedDays(NoContactProtocol $protocol): int
    {
        $startedAt = $protocol->streak_started_at;
        if ($startedAt === null) {
            return 0;
        }

        return (int) max(0, $startedAt->diffInDays(now()));
    }

    private function progressPercent(NoContactProtocol $protocol): int
    {
        $startedAt = $protocol->streak_started_at;
        $endsAt = $protocol->target_ends_at;

        if ($startedAt === null || $endsAt === null) {
            return 0;
        }

        $total = max($endsAt->getTimestamp() - $startedAt->getTimestamp(), 1);
        $elapsed = max(0, now()->getTimestamp() - $startedAt->getTimestamp());

        return (int) round(min(100, max(0, ($elapsed / $total) * 100)));
    }
}
