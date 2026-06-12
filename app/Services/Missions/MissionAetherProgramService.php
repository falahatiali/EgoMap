<?php

namespace App\Services\Missions;

use App\Models\User;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherUserProfile;
use Modules\AetherEngine\Services\AetherEngineService;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\MissionEngine\Models\MissionEnrollment;

class MissionAetherProgramService
{
    public function __construct(
        private AetherProfileService $profileService,
        private AetherEngineService $aetherEngine,
        private MissionAetherAdherenceService $adherence,
    ) {}

    /**
     * @param  array<string, mixed>  $wizard
     */
    public function generate(
        User $user,
        array $wizard,
        MissionEnrollment $enrollment,
        string $appliedTarget,
    ): AetherGeneratedProgram {
        $profile = $this->profileService->upsertForUser($user, $this->normalizeWizard($wizard));
        $program = $this->aetherEngine->generate($profile);

        $program->update([
            'mission_enrollment_id' => $enrollment->id,
            'applied_target' => $appliedTarget,
        ]);

        $this->adherence->syncEnrollmentProgress($enrollment->fresh(), $user);

        return $program->fresh();
    }

    public function loadWizardDefaults(?AetherUserProfile $profile, MissionEnrollment $enrollment): array
    {
        $values = $enrollment->field_values ?? [];
        $gymDayCount = count(is_array($values['gym_days'] ?? null) ? $values['gym_days'] : []);

        if ($profile === null) {
            return [
                'age' => 28,
                'gender' => 'male',
                'height_cm' => 175,
                'weight_kg' => 75,
                'body_fat_percent' => null,
                'training_experience' => 'intermediate',
                'primary_goal' => 'muscle_gain',
                'training_days_per_week' => max(3, $gymDayCount),
                'session_duration' => '45_60',
                'preferred_workout_time' => 'evening',
                'equipment' => 'full_gym',
                'injury_tags' => [],
                'dietary_pattern' => 'omnivore',
                'cooking_ability' => 'simple',
                'coaching_tone' => 'gentle',
                'motivation_style' => 'feeling_strong',
                'training_style' => 'heavy_weights',
            ];
        }

        return [
            'age' => $profile->age,
            'gender' => $profile->gender->value,
            'height_cm' => $profile->height_cm,
            'weight_kg' => (float) $profile->weight_kg,
            'body_fat_percent' => $profile->body_fat_percent !== null ? (float) $profile->body_fat_percent : null,
            'training_experience' => $profile->training_experience->value,
            'primary_goal' => $profile->primary_goal->value,
            'training_days_per_week' => $profile->training_days_per_week,
            'session_duration' => $profile->session_duration->value,
            'preferred_workout_time' => $profile->preferred_workout_time?->value ?? 'evening',
            'equipment' => $profile->equipment->value,
            'injury_tags' => $profile->injury_tags ?? [],
            'dietary_pattern' => $profile->dietary_pattern->value,
            'cooking_ability' => $profile->cooking_ability->value,
            'coaching_tone' => $profile->coaching_tone->value,
            'motivation_style' => $profile->motivation_style->value,
            'training_style' => is_string($profile->metadata['training_style'] ?? null)
                ? $profile->metadata['training_style']
                : 'heavy_weights',
        ];
    }

    /**
     * @param  array<string, mixed>  $wizard
     * @return array<string, mixed>
     */
    private function normalizeWizard(array $wizard): array
    {
        return [
            'age' => (int) $wizard['age'],
            'gender' => (string) $wizard['gender'],
            'height_cm' => (int) $wizard['height_cm'],
            'weight_kg' => (float) $wizard['weight_kg'],
            'body_fat_percent' => filled($wizard['body_fat_percent'] ?? null) ? (float) $wizard['body_fat_percent'] : null,
            'training_experience' => (string) ($wizard['training_experience'] ?? 'intermediate'),
            'primary_goal' => (string) $wizard['primary_goal'],
            'training_days_per_week' => (int) $wizard['training_days_per_week'],
            'session_duration' => (string) $wizard['session_duration'],
            'preferred_workout_time' => (string) ($wizard['preferred_workout_time'] ?? 'evening'),
            'equipment' => (string) $wizard['equipment'],
            'injury_tags' => is_array($wizard['injury_tags'] ?? null) ? $wizard['injury_tags'] : [],
            'dietary_pattern' => (string) $wizard['dietary_pattern'],
            'cooking_ability' => (string) ($wizard['cooking_ability'] ?? 'simple'),
            'coaching_tone' => (string) ($wizard['coaching_tone'] ?? 'gentle'),
            'motivation_style' => (string) $wizard['motivation_style'],
            'metadata' => [
                'training_style' => (string) ($wizard['training_style'] ?? 'heavy_weights'),
            ],
            'stress_level' => 5,
            'sleep_hours' => 7.5,
        ];
    }
}
