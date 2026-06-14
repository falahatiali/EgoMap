<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use App\Services\Profile\UserAetherProgramHistoryService;
use App\Support\LocaleConfig;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramExerciseSet;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;
use Modules\AetherEngine\Services\ExerciseMedia\ExerciseMediaResolver;
use Modules\MissionEngine\Support\MissionLocalizedText;

final class AetherApiService
{
    public function __construct(
        private UserAetherProgramHistoryService $history,
        private AetherWorkoutLogService $workoutLogs,
        private ExerciseMediaResolver $media,
    ) {}

    public function resolveLocale(?string $acceptLanguage = null): string
    {
        return LocaleConfig::resolve($acceptLanguage ?? app()->getLocale());
    }

    /**
     * @return array{programs: list<array<string, mixed>>}
     */
    public function programsForUser(User $user, string $locale, ?string $missionEnrollmentUuid = null): array
    {
        $query = AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->where('user_id', $user->id)
            ->with('missionEnrollment')
            ->latest('id');

        if ($missionEnrollmentUuid !== null) {
            $query->whereHas('missionEnrollment', fn ($q) => $q->where('uuid', $missionEnrollmentUuid));
        }

        return [
            'programs' => $query->get()
                ->map(fn (AetherGeneratedProgram $program): array => $this->mapProgramSummary($program, $locale, $user))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{program: array<string, mixed>}
     */
    public function programDetail(User $user, AetherGeneratedProgram $program, string $locale): array
    {
        abort_unless($program->user_id === $user->id, 403);

        $program->loadMissing([
            'profile',
            'scheduleEntries',
            'workoutDays.exercises.prescriptionSets',
            'nutritionDays.meals.ingredients',
        ]);

        $setLogs = $this->workoutLogs->logsForProgram($user, $program)->keyBy('aether_program_exercise_set_id');

        return [
            'program' => array_merge(
                $this->mapProgramSummary($program, $locale, $user),
                [
                    'coach' => [
                        'title' => $program->coach_title,
                        'week_focus' => $program->coach_week_focus,
                        'mindset_focus' => $program->coach_mindset_focus,
                        'habit_stack' => $program->coach_habit_stack,
                        'recovery_strategy' => $program->coach_recovery_strategy,
                        'supplement_advice' => $program->coach_supplement_advice,
                        'disclaimer' => $program->coach_disclaimer,
                    ],
                    'metabolic' => [
                        'bmr' => $program->metabolic_bmr,
                        'tdee' => $program->metabolic_tdee,
                        'target_calories' => $program->metabolic_target_calories,
                        'protein_grams' => $program->metabolic_protein_grams,
                        'fat_grams' => $program->metabolic_fat_grams,
                        'carb_grams' => $program->metabolic_carb_grams,
                    ],
                    'workout_days' => $program->workoutDays
                        ->sortBy('day_index')
                        ->values()
                        ->map(fn (AetherProgramWorkoutDay $day): array => [
                            'id' => $day->id,
                            'day_index' => $day->day_index,
                            'label' => $day->label,
                            'focus' => $day->focus,
                            'exercises' => $day->exercises
                                ->sortBy('sort_order')
                                ->values()
                                ->map(function ($exercise) use ($user, $setLogs): array {
                                    $display = $this->workoutLogs->displayExercise($exercise, $user);

                                    return [
                                        'id' => $exercise->id,
                                        'slug' => $display['slug'],
                                        'name' => $display['name'],
                                        'muscle_group' => $display['muscle_group'],
                                        'media_url' => $this->media->resolveBySlug($display['slug'])['gif_url'] ?? null,
                                        'sets' => $exercise->prescriptionSets
                                            ->sortBy('set_number')
                                            ->values()
                                            ->map(fn (AetherProgramExerciseSet $set): array => [
                                                'id' => $set->id,
                                                'set_number' => $set->set_number,
                                                'target_reps_min' => $set->target_reps_min,
                                                'target_reps_max' => $set->target_reps_max,
                                                'rest_seconds' => $set->rest_seconds,
                                                'completed' => (bool) ($setLogs->get($set->id)?->completed ?? false),
                                            ])
                                            ->all(),
                                    ];
                                })
                                ->all(),
                        ])
                        ->all(),
                    'nutrition_days' => $program->nutritionDays
                        ->sortBy('day_index')
                        ->values()
                        ->map(fn ($day): array => [
                            'id' => $day->id,
                            'day_index' => $day->day_index,
                            'label' => $day->label,
                            'meals' => $day->meals
                                ->sortBy('sort_order')
                                ->values()
                                ->map(fn ($meal): array => [
                                    'id' => $meal->id,
                                    'meal_type' => $meal->meal_type,
                                    'name' => $meal->name,
                                    'calories' => $meal->calories,
                                    'protein_g' => $meal->protein_g,
                                    'ingredients' => $meal->ingredients
                                        ->map(fn ($ingredient): array => [
                                            'name' => $ingredient->name,
                                            'quantity' => $ingredient->quantity,
                                            'unit' => $ingredient->unit,
                                        ])
                                        ->all(),
                                ])
                                ->all(),
                        ])
                        ->all(),
                ],
            ),
        ];
    }

    /**
     * @return array{set_log: array<string, mixed>, adherence_percent: int}
     */
    public function toggleWorkoutSet(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExerciseSet $exerciseSet,
        AetherProgramWorkoutDay $workoutDay,
    ): array {
        abort_unless($program->user_id === $user->id, 403);
        abort_unless($workoutDay->aether_generated_program_id === $program->id, 404);
        abort_unless($exerciseSet->programExercise?->aether_program_workout_day_id === $workoutDay->id, 404);

        $log = $this->workoutLogs->toggleSet($user, $program, $exerciseSet, $workoutDay);

        return [
            'set_log' => [
                'exercise_set_id' => $exerciseSet->id,
                'completed' => $log->completed,
                'completed_reps' => $log->completed_reps,
                'completed_at' => $log->completed_at?->toIso8601String(),
            ],
            'adherence_percent' => $this->workoutLogs->weekCompletionPercent($user, $program),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProgramSummary(AetherGeneratedProgram $program, string $locale, User $user): array
    {
        $missionTitle = null;

        if ($program->missionEnrollment !== null) {
            $snapshot = is_array($program->missionEnrollment->template_snapshot)
                ? $program->missionEnrollment->template_snapshot
                : [];
            $missionTitle = $program->missionEnrollment->title
                ?: MissionLocalizedText::forLocale($snapshot['title'] ?? '', $locale);
        }

        return [
            'uuid' => $program->uuid,
            'version' => $program->version,
            'status' => $program->status->value,
            'applied_target' => $program->applied_target,
            'split' => $program->split?->value,
            'summary' => $this->history->summaryForProgram($program, $locale),
            'mission_enrollment_uuid' => $program->missionEnrollment?->uuid,
            'mission_title' => $missionTitle !== '' ? $missionTitle : null,
            'adherence_percent' => $program->applied_target === 'workout'
                ? $this->workoutLogs->weekCompletionPercent($user, $program)
                : null,
            'created_at' => $program->created_at?->toIso8601String(),
        ];
    }
}
