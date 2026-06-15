<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\AetherEngine\Enums\ProgramEditAction;
use Modules\AetherEngine\Enums\WorkoutSessionStatus;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramExercise;
use Modules\AetherEngine\Models\AetherProgramExerciseOverride;
use Modules\AetherEngine\Models\AetherProgramExerciseSet;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;
use Modules\AetherEngine\Models\AetherWorkoutSession;
use Modules\AetherEngine\Models\AetherWorkoutSetLog;

class AetherWorkoutLogService
{
    public function __construct(
        private AetherWorkoutSessionService $sessions,
        private AetherProgramEditEventService $editEvents,
    ) {}

    public function toggleSet(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExerciseSet $exerciseSet,
        AetherProgramWorkoutDay $workoutDay,
    ): AetherWorkoutSetLog {
        $session = $this->sessions->startOrResume($user, $program, $workoutDay);

        $log = AetherWorkoutSetLog::query()->firstOrNew([
            'user_id' => $user->id,
            'aether_workout_session_id' => $session->id,
            'aether_program_exercise_set_id' => $exerciseSet->id,
        ]);

        $log->completed = ! $log->completed;
        $log->skipped = false;
        $log->completed_reps = $log->completed_reps ?? $exerciseSet->target_reps_max ?? $exerciseSet->target_reps_min;
        $log->completed_at = $log->completed ? now() : null;
        $log->save();

        $this->refreshSessionStatus($session);

        return $log;
    }

    /**
     * @return Collection<int, AetherWorkoutSetLog>
     */
    public function logsForProgram(User $user, AetherGeneratedProgram $program): Collection
    {
        $sessionIds = AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return collect();
        }

        return AetherWorkoutSetLog::query()
            ->where('user_id', $user->id)
            ->whereIn('aether_workout_session_id', $sessionIds)
            ->with('programExerciseSet')
            ->get();
    }

    public function weekCompletionPercent(User $user, AetherGeneratedProgram $program): int
    {
        $program->loadMissing('workoutDays.exercises.prescriptionSets');

        $totalSets = $program->workoutDays
            ->flatMap(fn ($day) => $day->exercises)
            ->flatMap(fn ($exercise) => $exercise->prescriptionSets)
            ->count();

        if ($totalSets === 0) {
            return 0;
        }

        $completed = $this->logsForProgram($user, $program)
            ->where('completed', true)
            ->count();

        return (int) min(100, round(($completed / $totalSets) * 100));
    }

    public function applySwap(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExercise $exercise,
        string $slug,
        string $name,
        string $muscleGroup,
    ): AetherProgramExerciseOverride {
        $before = $this->displayExercise($exercise, $user);

        $override = AetherProgramExerciseOverride::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'aether_program_exercise_id' => $exercise->id,
            ],
            [
                'slug' => $slug,
                'name' => $name,
                'muscle_group' => $muscleGroup,
            ],
        );

        $this->editEvents->record(
            $user,
            $program,
            $exercise,
            ProgramEditAction::Replace,
            $before,
            $this->displayExercise($exercise, $user),
        );

        return $override;
    }

    public function updateExercisePrescription(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExercise $exercise,
        int $sets,
        string $reps,
        int $restSeconds,
    ): AetherProgramExerciseOverride {
        $before = $this->displayExercise($exercise, $user);

        $override = AetherProgramExerciseOverride::query()->firstOrNew([
            'user_id' => $user->id,
            'aether_program_exercise_id' => $exercise->id,
        ]);

        $override->slug = $override->slug ?? $exercise->slug;
        $override->name = $override->name ?? $exercise->name;
        $override->muscle_group = $override->muscle_group ?? $exercise->muscle_group;
        $override->sets = $sets;
        $override->reps = $reps;
        $override->rest_seconds = $restSeconds;
        $override->save();

        $this->editEvents->record(
            $user,
            $program,
            $exercise,
            ProgramEditAction::Edit,
            $before,
            $this->displayExercise($exercise, $user),
        );

        return $override;
    }

    /**
     * @return array{name: string, slug: string, sets: int, reps: string, rest_seconds: int, muscle_group: string}
     */
    public function displayExercise(AetherProgramExercise $exercise, User $user): array
    {
        $override = AetherProgramExerciseOverride::query()
            ->where('user_id', $user->id)
            ->where('aether_program_exercise_id', $exercise->id)
            ->first();

        if ($override === null) {
            return [
                'name' => $exercise->name,
                'slug' => $exercise->slug,
                'sets' => $exercise->sets,
                'reps' => $exercise->reps,
                'rest_seconds' => $exercise->rest_seconds,
                'muscle_group' => $exercise->muscle_group,
            ];
        }

        return [
            'name' => $override->name,
            'slug' => $override->slug,
            'sets' => $override->sets ?? $exercise->sets,
            'reps' => $override->reps ?? $exercise->reps,
            'rest_seconds' => $override->rest_seconds ?? $exercise->rest_seconds,
            'muscle_group' => is_string($override->muscle_group) ? $override->muscle_group : $override->muscle_group->value,
        ];
    }

    /**
     * Log (or update) the weight used for a specific set in the current session.
     *
     * @param  float  $weightKg  Weight in kilograms; use 0 to clear.
     */
    public function logWeight(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExerciseSet $exerciseSet,
        AetherProgramWorkoutDay $workoutDay,
        float $weightKg,
    ): AetherWorkoutSetLog {
        $session = $this->sessions->startOrResume($user, $program, $workoutDay);

        $log = AetherWorkoutSetLog::query()->firstOrNew([
            'user_id' => $user->id,
            'aether_workout_session_id' => $session->id,
            'aether_program_exercise_set_id' => $exerciseSet->id,
        ]);

        $log->weight_kg = $weightKg > 0 ? $weightKg : null;
        $log->save();

        return $log;
    }

    /**
     * Compute the suggested weight for a set based on the previous program
     * version's average logged weight + 2.5 kg progressive overload.
     *
     * Returns null when there is no prior data.
     */
    public function suggestedWeightForSet(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramExerciseSet $exerciseSet,
    ): ?float {
        // Find the most recent completed log for any set on the same exercise slug
        // within the same program (prior sessions = prior weeks).
        $exerciseSlug = $exerciseSet->programExercise?->slug;

        if ($exerciseSlug === null) {
            return null;
        }

        $sessionIds = AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->orderByDesc('id')
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return null;
        }

        // Find all logs with a weight for the same exercise slug.
        $avgWeight = AetherWorkoutSetLog::query()
            ->whereIn('aether_workout_session_id', $sessionIds)
            ->where('user_id', $user->id)
            ->whereNotNull('weight_kg')
            ->whereHas('programExerciseSet.programExercise', function ($q) use ($exerciseSlug): void {
                $q->where('slug', $exerciseSlug);
            })
            ->avg('weight_kg');

        if ($avgWeight === null) {
            return null;
        }

        // Round to nearest 0.5 kg after adding the overload increment.
        return round(((float) $avgWeight + 2.5) * 2) / 2;
    }

    /**
     * @return Collection<int, AetherWorkoutSetLog>
     */
    public function weightLogsForProgram(User $user, AetherGeneratedProgram $program): Collection
    {
        $sessionIds = AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return collect();
        }

        return AetherWorkoutSetLog::query()
            ->where('user_id', $user->id)
            ->whereIn('aether_workout_session_id', $sessionIds)
            ->whereNotNull('weight_kg')
            ->get(['aether_program_exercise_set_id', 'weight_kg']);
    }

    private function refreshSessionStatus(AetherWorkoutSession $session): void
    {
        $session->loadMissing('workoutDay.exercises.prescriptionSets', 'setLogs');

        $totalSets = $session->workoutDay?->exercises
            ->flatMap(fn ($exercise) => $exercise->prescriptionSets)
            ->count() ?? 0;

        if ($totalSets === 0) {
            return;
        }

        $completed = $session->setLogs->where('completed', true)->count();

        if ($completed >= $totalSets) {
            $session->update([
                'status' => WorkoutSessionStatus::Completed,
                'completed_at' => now(),
            ]);

            return;
        }

        if ($completed > 0) {
            $session->update(['status' => WorkoutSessionStatus::PartiallyCompleted]);
        }
    }
}
