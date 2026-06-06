<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Ai\Agents\ProgramNarrativeAgent;
use Modules\AetherEngine\Contracts\ProgramEnrichmentInterface;
use Modules\AetherEngine\Data\GeneratedProgramPayload;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Models\AetherUserProfile;

class ProgramEnrichmentService implements ProgramEnrichmentInterface
{
    public function __construct(private AetherPromptBuilder $promptBuilder) {}

    /**
     * Coach-facing copy only — structured workout and nutrition data live in relational tables.
     *
     * @return array<string, mixed>
     */
    public function enrich(AetherUserProfile $profile, GeneratedProgramPayload $payload): array
    {
        if (! config('aether.ai_enrichment_enabled', false)) {
            return $this->fallbackNarrative($profile, $payload);
        }

        $template = $this->promptBuilder->resolveTemplate($profile);

        if ($template === null) {
            return $this->fallbackNarrative($profile, $payload);
        }

        try {
            $prompt = $this->promptBuilder->buildEnrichmentPrompt($profile, $payload, $template);
            $response = (new ProgramNarrativeAgent)->prompt($prompt);

            if (is_array($response->structured)) {
                return $this->normalizeAiNarrative($response->structured);
            }
        } catch (\Throwable) {
            // Graceful degradation when AI provider is unavailable.
        }

        return $this->fallbackNarrative($profile, $payload);
    }

    /**
     * @param  array<string, mixed>  $narrative
     * @return array<string, mixed>
     */
    private function normalizeAiNarrative(array $narrative): array
    {
        $firstWeek = is_array($narrative['weeks'][0] ?? null) ? $narrative['weeks'][0] : [];

        return array_filter([
            'title' => $narrative['title'] ?? null,
            'week_focus' => $firstWeek['focus'] ?? $narrative['week_focus'] ?? null,
            'mindset_focus' => $firstWeek['mindset_focus'] ?? $narrative['mindset_focus'] ?? null,
            'habit_stack' => $firstWeek['habit_stack'] ?? $narrative['habit_stack'] ?? null,
            'recovery_strategy' => $narrative['recovery_strategy'] ?? null,
            'supplement_advice' => $narrative['supplement_advice'] ?? null,
            'disclaimer' => $narrative['disclaimer'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    private function fallbackNarrative(AetherUserProfile $profile, GeneratedProgramPayload $payload): array
    {
        $goalLabel = str_replace('_', ' ', $profile->primary_goal->value);

        return [
            'title' => '12-Week Transformation: '.ucwords($goalLabel),
            'week_focus' => match ($profile->primary_goal) {
                PrimaryGoal::FatLoss => 'Foundation & calorie discipline',
                PrimaryGoal::MuscleGain => 'Foundation & progressive overload',
                default => 'Foundation & form',
            },
            'mindset_focus' => 'Consistency over intensity this week.',
            'habit_stack' => 'After every workout, write one sentence about how you feel.',
            'recovery_strategy' => 'Sleep at least '.(int) $profile->sleep_hours.' hours; take a 10-min walk on rest days.',
            'supplement_advice' => $this->supplementAdvice($profile),
            'disclaimer' => 'Consult a physician before starting any new exercise or diet program.',
        ];
    }

    private function supplementAdvice(AetherUserProfile $profile): string
    {
        $supplements = $profile->supplements ?? [];

        if ($supplements === [] || in_array('none', $supplements, true)) {
            return 'Whole foods first. Consider whey protein post-workout if protein targets are hard to hit.';
        }

        return 'Continue your current stack ('.implode(', ', $supplements).'). Prioritize protein and creatine if muscle gain is the goal.';
    }
}
