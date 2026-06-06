<?php

namespace App\Services\Missions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\AetherEngine\Enums\ProgramScheduleEntryType;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherUserProfile;
use Modules\AetherEngine\Services\AetherEngineService;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Support\MissionLocalizedText;

class MissionAetherProgramService
{
    /**
     * @var array<int, string>
     */
    private const ISO_WEEKDAY_TO_DAY_KEY = [
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
        7 => 'sun',
    ];

    public function __construct(
        private AetherProfileService $profileService,
        private AetherEngineService $aetherEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $wizard
     */
    public function generate(User $user, array $wizard): AetherGeneratedProgram
    {
        $profile = $this->profileService->upsertForUser($user, $this->normalizeWizard($wizard));

        return $this->aetherEngine->generate($profile);
    }

    /**
     * @return list<array{day: string, focus: string, notes: string}>
     */
    public function workoutPlanRowsForLocale(AetherGeneratedProgram $program, string $locale): array
    {
        $program->loadMissing(['scheduleEntries', 'workoutDays.exercises']);

        $workoutDays = $program->workoutDays->keyBy('day_index');
        $rows = [];

        foreach ($program->scheduleEntries as $entry) {
            if ($entry->entry_type !== ProgramScheduleEntryType::Workout) {
                continue;
            }

            $day = $workoutDays->get($entry->workout_day_index);

            if ($day === null) {
                continue;
            }

            $dayKey = self::ISO_WEEKDAY_TO_DAY_KEY[$entry->iso_weekday] ?? 'sat';
            $exerciseLines = $day->exercises
                ->map(fn ($exercise): string => sprintf(
                    '%s — %d×%s',
                    $exercise->name,
                    $exercise->sets,
                    $exercise->reps,
                ))
                ->implode("\n");

            $rows[] = [
                'day' => $dayKey,
                'focus' => $day->label,
                'notes' => trim(($day->motivation ?? '')."\n".$exerciseLines),
            ];
        }

        if ($rows === []) {
            foreach ($program->workoutDays as $index => $day) {
                $rows[] = [
                    'day' => array_values(self::ISO_WEEKDAY_TO_DAY_KEY)[min($index, 6)],
                    'focus' => $day->label,
                    'notes' => $day->exercises
                        ->map(fn ($exercise): string => $exercise->name.' '.$exercise->sets.'×'.$exercise->reps)
                        ->implode("\n"),
                ];
            }
        }

        return $rows;
    }

    public function mealPlanNotesForLocale(AetherGeneratedProgram $program, string $locale): string
    {
        $program->loadMissing(['nutritionDays.meals']);
        $lines = [];

        if ($program->metabolic_target_calories !== null) {
            $lines[] = __('missions.ai_meal_summary_macros', [
                'calories' => $program->metabolic_target_calories,
                'protein' => $program->metabolic_protein_grams ?? 0,
            ]);
        }

        foreach ($program->nutritionDays as $day) {
            $meals = $day->meals
                ->map(fn ($meal): string => $meal->name.' ('.$meal->calories.' kcal)')
                ->implode(' · ');

            $lines[] = __('missions.ai_meal_day_line', [
                'day' => $day->day_index,
                'meals' => $meals,
            ]);
        }

        if (is_string($program->shopping_list_summary) && $program->shopping_list_summary !== '') {
            $lines[] = __('missions.ai_meal_shopping').': '.$program->shopping_list_summary;
        }

        return implode("\n\n", array_filter($lines));
    }

    /**
     * @param  list<array{day: string, focus: string, notes: string}>  $rows
     * @return list<array{day: string, focus: array<string, string>, notes: array<string, string>}>
     */
    public function persistWorkoutPlanRows(MissionEnrollment $enrollment, array $rows, string $locale): array
    {
        $existing = is_array($enrollment->field_values['workout_plan'] ?? null)
            ? $enrollment->field_values['workout_plan']
            : [];
        $persisted = [];

        foreach ($rows as $row) {
            $previous = collect($existing)->firstWhere('day', $row['day']) ?? [];

            $persisted[] = [
                'day' => $row['day'],
                'focus' => MissionLocalizedText::merge($previous['focus'] ?? '', $row['focus'], $locale),
                'notes' => MissionLocalizedText::merge($previous['notes'] ?? '', $row['notes'], $locale),
            ];
        }

        return $persisted;
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
                'injuries_limitations' => '',
                'dietary_pattern' => 'omnivore',
                'cooking_ability' => 'simple',
                'allergies_text' => '',
                'coaching_tone' => 'technical',
                'motivation_style' => 'feeling_strong',
                'favorite_exercises_text' => '',
                'disliked_exercises_text' => '',
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
            'injuries_limitations' => (string) ($profile->injuries_limitations ?? ''),
            'dietary_pattern' => $profile->dietary_pattern->value,
            'cooking_ability' => $profile->cooking_ability->value,
            'allergies_text' => implode(', ', $profile->allergies ?? []),
            'coaching_tone' => $profile->coaching_tone->value,
            'motivation_style' => $profile->motivation_style->value,
            'favorite_exercises_text' => implode(', ', $profile->favorite_exercises ?? []),
            'disliked_exercises_text' => implode(', ', $profile->disliked_exercises ?? []),
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
            'training_experience' => (string) $wizard['training_experience'],
            'primary_goal' => (string) $wizard['primary_goal'],
            'training_days_per_week' => (int) $wizard['training_days_per_week'],
            'session_duration' => (string) $wizard['session_duration'],
            'preferred_workout_time' => (string) $wizard['preferred_workout_time'],
            'equipment' => (string) $wizard['equipment'],
            'injuries_limitations' => (string) ($wizard['injuries_limitations'] ?? ''),
            'dietary_pattern' => (string) $wizard['dietary_pattern'],
            'cooking_ability' => (string) $wizard['cooking_ability'],
            'allergies' => $this->csvToList($wizard['allergies_text'] ?? ''),
            'coaching_tone' => (string) $wizard['coaching_tone'],
            'motivation_style' => (string) $wizard['motivation_style'],
            'favorite_exercises' => $this->csvToList($wizard['favorite_exercises_text'] ?? ''),
            'disliked_exercises' => $this->csvToList($wizard['disliked_exercises_text'] ?? ''),
            'stress_level' => 5,
            'sleep_hours' => 7.5,
        ];
    }

    /**
     * @return list<string>
     */
    private function csvToList(string $value): array
    {
        return Collection::make(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
