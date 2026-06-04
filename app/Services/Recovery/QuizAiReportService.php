<?php

namespace App\Services\Recovery;

use App\Ai\Agents\Recovery\AssessmentAnalysisAgent;
use App\Ai\Agents\Recovery\TruthFlashAgent;
use App\Models\QuizResult;
use App\Models\QuizSession;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\Log;

class QuizAiReportService
{
    public function __construct(
        private readonly RecoveryAiContextService $contextService,
        private readonly RecoveryAiPromptService $prompts,
    ) {}

    public function generateForResult(QuizResult $result, QuizSession $session): QuizResult
    {
        $freeReport = is_array($result->free_report) ? $result->free_report : [];

        if (($freeReport['template'] ?? '') !== 'reboot_protocol') {
            return $result;
        }

        $locale = LocaleConfig::resolve($session->locale);
        $payload = $this->prompts->isEnabled()
            ? $this->generateWithAi($session, $locale)
            : $this->buildFallbackPayload($freeReport, $locale);

        $result->update([
            'premium_report' => $payload,
            'ai_model' => $payload['source'] === 'ai' ? $this->prompts->modelLabel() : null,
            'ai_prompt_version' => $this->prompts->promptVersion(),
        ]);

        return $result->refresh();
    }

    /**
     * @param  array<string, mixed>  $freeReport
     * @return array<string, mixed>
     */
    public function buildFallbackPayload(array $freeReport, string $locale): array
    {
        $phase = LocaleConfig::pick($freeReport['phase'] ?? [], $locale);
        $mainRisk = LocaleConfig::pick($freeReport['main_risk'] ?? [], $locale);
        $prescription = LocaleConfig::pick($freeReport['first_prescription'] ?? [], $locale);
        $patterns = collect($freeReport['detected_patterns'] ?? [])
            ->map(fn (array $pattern): string => LocaleConfig::pick($pattern, $locale))
            ->filter()
            ->values()
            ->all();

        $steps = collect($freeReport['next_steps'] ?? [])
            ->map(fn (array $step): string => LocaleConfig::pick($step, $locale))
            ->filter()
            ->values()
            ->take(3)
            ->all();

        while (count($steps) < 3) {
            $steps[] = $prescription;
        }

        $truths = $this->fallbackTruths($freeReport, $locale);

        return [
            'version' => $this->prompts->promptVersion(),
            'locale' => $locale,
            'source' => 'fallback',
            'generated_at' => now()->toIso8601String(),
            'assessment' => [
                'summary' => $prescription,
                'recovery_phase' => $phase,
                'main_risk' => $mainRisk,
                'attachment_pattern' => $patterns[0] ?? LocaleConfig::translate('recovery_ai.default_attachment_pattern', $locale),
                'recommendations' => array_slice($steps, 0, 3),
            ],
            'truth_flashes' => $truths,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateWithAi(QuizSession $session, string $locale): array
    {
        try {
            $context = $this->contextService->forQuizSession($session);

            $assessmentPrompt = $this->prompts->buildPrompt(
                (new AssessmentAnalysisAgent($locale))->instructions(),
                $context,
                'Produce a personalized recovery assessment. Max 500 words total across fields.',
            );

            $assessment = $this->prompts->prompt(
                new AssessmentAnalysisAgent($locale),
                $assessmentPrompt,
            );

            $truthPrompt = $this->prompts->buildPrompt(
                (new TruthFlashAgent($locale))->instructions(),
                $context,
                'Return exactly three hard truths as truth_one, truth_two, truth_three.',
            );

            $truths = $this->prompts->prompt(
                new TruthFlashAgent($locale),
                $truthPrompt,
            );

            return [
                'version' => $this->prompts->promptVersion(),
                'locale' => $locale,
                'source' => 'ai',
                'generated_at' => now()->toIso8601String(),
                'assessment' => [
                    'summary' => (string) ($assessment['summary'] ?? ''),
                    'recovery_phase' => (string) ($assessment['recovery_phase'] ?? ''),
                    'main_risk' => (string) ($assessment['main_risk'] ?? ''),
                    'attachment_pattern' => (string) ($assessment['attachment_pattern'] ?? ''),
                    'recommendations' => array_values(array_filter([
                        (string) ($assessment['recommendation_one'] ?? ''),
                        (string) ($assessment['recommendation_two'] ?? ''),
                        (string) ($assessment['recommendation_three'] ?? ''),
                    ])),
                ],
                'truth_flashes' => array_values(array_filter([
                    (string) ($truths['truth_one'] ?? ''),
                    (string) ($truths['truth_two'] ?? ''),
                    (string) ($truths['truth_three'] ?? ''),
                ])),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Quiz AI report generation failed', [
                'session_id' => $session->id,
                'message' => $exception->getMessage(),
            ]);

            $freeReport = is_array($session->result?->free_report) ? $session->result->free_report : [];

            return $this->buildFallbackPayload($freeReport, $locale);
        }
    }

    /**
     * @param  array<string, mixed>  $freeReport
     * @return list<string>
     */
    private function fallbackTruths(array $freeReport, string $locale): array
    {
        $patterns = collect($freeReport['detected_patterns'] ?? [])
            ->map(fn (array $pattern): string => LocaleConfig::pick($pattern, $locale))
            ->filter()
            ->take(2)
            ->values()
            ->all();

        $phaseNarrative = LocaleConfig::pick($freeReport['phase_narrative'] ?? [], $locale);

        $truths = $patterns;

        if ($phaseNarrative !== '') {
            $truths[] = $phaseNarrative;
        }

        if (count($truths) < 3) {
            $truths[] = LocaleConfig::translate('recovery_ai.fallback.truth_contact', $locale);
        }

        return array_slice(array_values(array_unique($truths)), 0, 3);
    }
}
