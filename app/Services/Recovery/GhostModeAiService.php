<?php

namespace App\Services\Recovery;

use App\Ai\Agents\Recovery\BlackholeAnalysisAgent;
use App\Ai\Agents\Recovery\EmergencySupportAgent;
use App\Ai\Agents\Recovery\TruthFlashAgent;
use App\Enums\GhostModeEventType;
use App\Enums\SessionStatus;
use App\Models\GhostModeEvent;
use App\Models\NoContactProtocol;
use App\Models\QuizSession;
use App\Models\User;
use App\Support\LocaleConfig;
use App\Support\RebootProtocolQuiz;
use Illuminate\Support\Facades\Log;

readonly class GhostModeAiService
{
    public function __construct(
        private RecoveryAiContextService $contextService,
        private QuizAiReportService $quizAiReport,
        private RecoveryAiPromptService $prompts,
    ) {}

    /**
     * @return array{message: string, exercise: string, source: string}
     */
    public function emergencyMessage(?User $user = null, ?NoContactProtocol $protocol = null): array
    {
        $locale = app()->getLocale();
        $context = $this->contextService->forGhostMode($user, $protocol);

        if ($this->prompts->isEnabled()) {
            try {
                $prompt = $this->prompts->buildPrompt(
                    (new EmergencySupportAgent($locale))->instructions(),
                    $context,
                    'Generate emergency support for this moment.',
                );

                $response = $this->prompts->prompt(
                    new EmergencySupportAgent($locale),
                    $prompt,
                );

                $result = [
                    'message' => (string) ($response['message'] ?? ''),
                    'exercise' => (string) ($response['exercise'] ?? ''),
                    'source' => 'ai',
                ];

                $this->logEvent($protocol, GhostModeEventType::Emergency, null, null, $result);

                return $result;
            } catch (\Throwable $exception) {
                Log::warning('Ghost Mode emergency AI failed', ['message' => $exception->getMessage()]);
            }
        }

        return $this->fallbackEmergency($locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeBlackhole(string $message, ?User $user = null, ?NoContactProtocol $protocol = null): array
    {
        $locale = app()->getLocale();
        $context = $this->contextService->forBlackhole($message, $user, $protocol);

        if ($this->prompts->isEnabled()) {
            try {
                $prompt = $this->prompts->buildPrompt(
                    (new BlackholeAnalysisAgent($locale))->instructions(),
                    $context,
                    'Analyze the draft_message field.',
                );

                $response = $this->prompts->prompt(
                    new BlackholeAnalysisAgent($locale),
                    $prompt,
                );

                $result = [
                    'regret_probability' => (int) ($response['regret_probability'] ?? 70),
                    'dominant_emotions' => (string) ($response['dominant_emotions'] ?? ''),
                    'analysis' => (string) ($response['analysis'] ?? ''),
                    'rewrite_suggestion' => (string) ($response['rewrite_suggestion'] ?? ''),
                    'closing_line' => (string) ($response['closing_line'] ?? ''),
                    'commitment_suggestion' => (string) ($response['commitment_suggestion'] ?? ''),
                    'source' => 'ai',
                ];

                $this->logEvent($protocol, GhostModeEventType::Blackhole, null, $message, $result);

                return $result;
            } catch (\Throwable $exception) {
                Log::warning('Ghost Mode blackhole AI failed', ['message' => $exception->getMessage()]);
            }
        }

        return $this->fallbackBlackhole($message, $locale);
    }

    /**
     * @return list<string>
     */
    public function truthFlashes(?User $user = null, ?NoContactProtocol $protocol = null): array
    {
        $locale = app()->getLocale();
        $user ??= auth()->user();

        if ($user !== null) {
            $session = QuizSession::query()
                ->where('user_id', $user->id)
                ->whereHas('quiz', fn ($query) => $query->where('slug', RebootProtocolQuiz::SLUG))
                ->where('status', SessionStatus::Completed)
                ->with('result')
                ->latest('completed_at')
                ->first();

            $premium = $session?->result?->premium_report;
            if (is_array($premium) && ! empty($premium['truth_flashes'])) {
                return $premium['truth_flashes'];
            }

            $freeReport = $session?->result?->free_report;
            if (is_array($freeReport) && ($freeReport['template'] ?? '') === 'reboot_protocol') {
                return $this->quizAiReport->buildFallbackPayload($freeReport, $locale)['truth_flashes'];
            }
        }

        if ($this->prompts->isEnabled()) {
            try {
                $context = $this->contextService->forGhostMode($user, $protocol);
                $prompt = $this->prompts->buildPrompt(
                    (new TruthFlashAgent($locale))->instructions(),
                    $context,
                    'Return exactly three hard truths.',
                );

                $response = $this->prompts->prompt(
                    new TruthFlashAgent($locale),
                    $prompt,
                );

                return array_values(array_filter([
                    (string) ($response['truth_one'] ?? ''),
                    (string) ($response['truth_two'] ?? ''),
                    (string) ($response['truth_three'] ?? ''),
                ]));
            } catch (\Throwable $exception) {
                Log::warning('Ghost Mode truth flash AI failed', ['message' => $exception->getMessage()]);
            }
        }

        return LocaleConfig::translateLines('recovery_ai.fallback.truth_flashes', $locale);
    }

    /**
     * @param  array<string, mixed>|null  $aiResult
     */
    public function logEvent(
        ?NoContactProtocol $protocol,
        GhostModeEventType $type,
        ?string $trigger = null,
        ?string $userText = null,
        ?array $aiResult = null,
    ): ?GhostModeEvent {
        if ($protocol === null) {
            return null;
        }

        return GhostModeEvent::query()->create([
            'no_contact_protocol_id' => $protocol->id,
            'type' => $type,
            'trigger' => $trigger,
            'user_text' => $userText,
            'ai_result' => $aiResult,
        ]);
    }

    /**
     * @return array{message: string, exercise: string, source: string}
     */
    private function fallbackEmergency(string $locale): array
    {
        return [
            'message' => LocaleConfig::translate('recovery_ai.fallback.emergency.message', $locale),
            'exercise' => LocaleConfig::translate('recovery_ai.fallback.emergency.exercise', $locale),
            'source' => 'fallback',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackBlackhole(string $message, string $locale): array
    {
        $length = mb_strlen(trim($message));

        return [
            'regret_probability' => min(95, 55 + (int) floor($length / 40)),
            'dominant_emotions' => LocaleConfig::translate('recovery_ai.fallback.blackhole.dominant_emotions', $locale),
            'analysis' => LocaleConfig::translate('recovery_ai.fallback.blackhole.analysis', $locale),
            'rewrite_suggestion' => LocaleConfig::translate('recovery_ai.fallback.blackhole.rewrite_suggestion', $locale),
            'closing_line' => LocaleConfig::translate('recovery_ai.fallback.blackhole.closing_line', $locale),
            'commitment_suggestion' => LocaleConfig::translate('recovery_ai.fallback.blackhole.commitment_suggestion', $locale),
            'source' => 'fallback',
        ];
    }
}
