<?php

namespace Modules\AetherEngine\Services;

use App\Support\Ai\ToonPromptBuilder;
use Modules\AetherEngine\Data\GeneratedProgramPayload;
use Modules\AetherEngine\Enums\MotivationStyle;
use Modules\AetherEngine\Models\AetherPromptTemplate;
use Modules\AetherEngine\Models\AetherUserProfile;

class AetherPromptBuilder
{
    public function resolveTemplate(AetherUserProfile $profile): ?AetherPromptTemplate
    {
        return AetherPromptTemplate::query()
            ->where('is_active', true)
            ->where('tone', $profile->coaching_tone->value)
            ->first()
            ?? AetherPromptTemplate::query()->where('is_active', true)->where('is_default', true)->first();
    }

    public function buildEnrichmentPrompt(AetherUserProfile $profile, GeneratedProgramPayload $payload, AetherPromptTemplate $template): string
    {
        $context = [
            'userProfile' => $this->buildUserProfile($profile),
            'deterministicProgram' => $this->buildDeterministicProgram($payload),
        ];

        $toneAddendum = trim($template->system_prompt) !== ''
            ? $template->system_prompt
            : 'Match tone_preference from userProfile.psychology.';

        return ToonPromptBuilder::build(
            $toneAddendum,
            $context,
            $template->task_prompt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildUserProfile(AetherUserProfile $profile): array
    {
        return [
            'basics' => [
                'age' => $profile->age,
                'gender' => $profile->gender->value,
                'height_cm' => $profile->height_cm,
                'current_weight_kg' => (float) $profile->weight_kg,
                'body_fat_percent' => $profile->body_fat_percent !== null ? (float) $profile->body_fat_percent : null,
                'experience' => $profile->training_experience->value,
            ],
            'goals' => [
                'primary' => $profile->primary_goal->value,
                'secondary' => $profile->secondary_goal?->value,
                'target_weight_kg' => $profile->target_weight_kg !== null ? (float) $profile->target_weight_kg : null,
                'weeks' => (int) config('aether.program_weeks', 12),
            ],
            'lifestyle' => [
                'stress_level' => $profile->stress_level,
                'sleep_hours' => (float) $profile->sleep_hours,
                'days_per_week' => $profile->training_days_per_week,
                'minutes_per_session' => $profile->session_duration->maxMinutes(),
                'preferred_time' => $profile->preferred_workout_time?->value,
                'equipment' => $this->mapEquipment($profile->equipment->value),
            ],
            'injuries' => $this->buildInjuries($profile),
            'nutrition' => [
                'diet_type' => $profile->dietary_pattern->value,
                'allergies' => $profile->allergies ?? [],
                'cooking_ability' => $profile->cooking_ability->value,
                'current_calories_estimate' => $profile->estimated_daily_calories,
            ],
            'psychology' => [
                'favorite_exercises' => $profile->favorite_exercises ?? [],
                'disliked_exercises' => $profile->disliked_exercises ?? [],
                'motivation_style' => $this->mapMotivationStyle($profile->motivation_style),
                'tone_preference' => $profile->coaching_tone->value,
            ],
            'supplements' => $profile->supplements ?? ['none'],
            'medical_conditions' => $profile->medical_conditions ?? 'none',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDeterministicProgram(GeneratedProgramPayload $payload): array
    {
        return [
            'split' => $payload->split->value,
            'metabolic' => $payload->metabolic->toArray(),
            'workout_days' => array_map(
                static fn ($day) => $day->toArray(),
                $payload->workoutDays,
            ),
            'nutrition_days' => array_map(
                static fn ($day) => $day->toArray(),
                $payload->nutritionDays,
            ),
            'schedule' => $payload->schedule->toArray(),
            'shopping_list_summary' => $payload->shoppingListSummary,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildInjuries(AetherUserProfile $profile): array
    {
        $tags = $profile->resolvedInjuryTags();

        if ($tags === []) {
            return [['body_part' => 'none', 'limitation' => 'none']];
        }

        return collect($tags)->map(fn (string $tag): array => [
            'body_part' => $tag,
            'limitation' => $profile->injuries_limitations ?? 'modify as needed',
        ])->all();
    }

    private function mapEquipment(string $equipment): string
    {
        return match ($equipment) {
            'resistance_bands' => 'bands',
            'bodyweight_only' => 'bodyweight',
            default => $equipment,
        };
    }

    private function mapMotivationStyle(MotivationStyle $style): string
    {
        return match ($style) {
            MotivationStyle::DataTracking => 'data',
            MotivationStyle::FeelingStrong => 'strength',
            default => $style->value,
        };
    }
}
