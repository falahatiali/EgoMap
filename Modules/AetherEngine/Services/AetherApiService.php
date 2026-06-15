<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use App\Services\Profile\UserAetherProgramHistoryService;
use App\Support\LocaleConfig;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramExerciseSet;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;
use Modules\AetherEngine\Models\AetherWorkoutSession;
use Modules\AetherEngine\Models\AetherWorkoutSetLog;
use Modules\AetherEngine\Services\ExerciseMedia\ExerciseMediaResolver;
use Modules\MissionEngine\Support\MissionLocalizedText;

final class AetherApiService
{
    public function __construct(
        private UserAetherProgramHistoryService $history,
        private AetherWorkoutLogService $workoutLogs,
        private ExerciseMediaResolver $media,
        private AetherCheckInService $checkIn,
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
        $weightLogs = $this->workoutLogs->weightLogsForProgram($user, $program)->keyBy('aether_program_exercise_set_id');

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
                                ->map(function ($exercise) use ($user, $program, $setLogs, $weightLogs): array {
                                    $display = $this->workoutLogs->displayExercise($exercise, $user);

                                    return [
                                        'id' => $exercise->id,
                                        'slug' => $display['slug'],
                                        'name' => $display['name'],
                                        'muscle_group' => $display['muscle_group'],
                                        'notes' => $exercise->notes,
                                        'rpe' => $exercise->rpe,
                                        'tempo' => $exercise->tempo,
                                        'default_weight_kg' => $exercise->default_weight_kg !== null ? (float) $exercise->default_weight_kg : null,
                                        'media_url' => $this->media->resolveBySlug($display['slug'])['gif_url'] ?? null,
                                        'sets' => $exercise->prescriptionSets
                                            ->sortBy('set_number')
                                            ->values()
                                            ->map(function (AetherProgramExerciseSet $set) use ($user, $program, $setLogs, $weightLogs): array {
                                                return [
                                                    'id' => $set->id,
                                                    'set_number' => $set->set_number,
                                                    'target_reps_min' => $set->target_reps_min,
                                                    'target_reps_max' => $set->target_reps_max,
                                                    'rest_seconds' => $set->rest_seconds,
                                                    'completed' => (bool) ($setLogs->get($set->id)?->completed ?? false),
                                                    'weight_kg' => $weightLogs->get($set->id)?->weight_kg !== null
                                                        ? (float) $weightLogs->get($set->id)?->weight_kg
                                                        : null,
                                                    'suggested_weight_kg' => $this->workoutLogs->suggestedWeightForSet($user, $program, $set),
                                                ];
                                            })
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
     * Return per-day volume data for the given program, grouped by day.
     *
     * Volume per set = weight_kg × completed_reps (falls back to target_reps_max).
     * Returns the last [days] calendar days that have any logged data.
     *
     * @return array{days: list<array{date: string, volume_kg: float, sets_logged: int}>}
     */
    public function volumeChart(User $user, AetherGeneratedProgram $program, int $days = 30): array
    {
        abort_unless($program->user_id === $user->id, 403);

        $sessionIds = AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return ['days' => []];
        }

        $logs = AetherWorkoutSetLog::query()
            ->with('programExerciseSet')
            ->where('user_id', $user->id)
            ->whereIn('aether_workout_session_id', $sessionIds)
            ->where('completed', true)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays($days))
            ->get();

        /** @var array<string, array{volume_kg: float, sets_logged: int}> $byDate */
        $byDate = [];

        foreach ($logs as $log) {
            $date = $log->completed_at->toDateString();
            $reps = $log->completed_reps
                ?? $log->programExerciseSet?->target_reps_max
                ?? $log->programExerciseSet?->target_reps_min
                ?? 1;
            $weight = (float) ($log->weight_kg ?? 0);
            $volume = $weight * $reps;

            if (! isset($byDate[$date])) {
                $byDate[$date] = ['volume_kg' => 0.0, 'sets_logged' => 0];
            }

            $byDate[$date]['volume_kg'] += $volume;
            $byDate[$date]['sets_logged']++;
        }

        ksort($byDate);

        return [
            'days' => array_map(
                fn (string $date, array $data): array => [
                    'date' => $date,
                    'volume_kg' => round($data['volume_kg'], 1),
                    'sets_logged' => $data['sets_logged'],
                ],
                array_keys($byDate),
                array_values($byDate),
            ),
        ];
    }

    /**
     * @return array{is_due: bool, current_week: int, last_check_in_date: string|null}
     */
    public function checkInStatus(User $user, AetherGeneratedProgram $program): array
    {
        abort_unless($program->user_id === $user->id, 403);

        return $this->checkIn->checkInStatus($user, $program);
    }

    /**
     * @param  array{sessions_completed: int, intensity_rating: int, had_pain: bool, pain_notes: string|null}  $data
     * @return array{check_in: array<string, mixed>, coaching: array<string, mixed>}
     */
    public function submitCheckIn(User $user, AetherGeneratedProgram $program, array $data): array
    {
        abort_unless($program->user_id === $user->id, 403);

        $record = $this->checkIn->saveCheckIn($user, $program, $data);
        $coaching = $this->checkIn->coachingResponse($data);

        return [
            'check_in' => [
                'id' => $record->id,
                'check_in_date' => $record->check_in_date->toDateString(),
                'workout_adherence_percent' => $record->workout_adherence_percent,
            ],
            'coaching' => $coaching,
        ];
    }

    /**
     * @return array{set_log: array<string, mixed>}
     */
    public function logSetWeight(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExerciseSet $exerciseSet,
        AetherProgramWorkoutDay $workoutDay,
        float $weightKg,
    ): array {
        abort_unless($program->user_id === $user->id, 403);
        abort_unless($workoutDay->aether_generated_program_id === $program->id, 404);
        abort_unless($exerciseSet->programExercise?->aether_program_workout_day_id === $workoutDay->id, 404);

        $log = $this->workoutLogs->logWeight($user, $program, $exerciseSet, $workoutDay, $weightKg);

        return [
            'set_log' => [
                'exercise_set_id' => $exerciseSet->id,
                'weight_kg' => $log->weight_kg !== null ? (float) $log->weight_kg : null,
            ],
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
