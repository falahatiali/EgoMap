<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Modules\AetherEngine\Enums\WorkoutSessionStatus;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;
use Modules\AetherEngine\Models\AetherWorkoutSession;

class AetherWorkoutSessionService
{
    public function startOrResume(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramWorkoutDay $workoutDay,
    ): AetherWorkoutSession {
        $session = AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->where('aether_program_workout_day_id', $workoutDay->id)
            ->whereIn('status', [
                WorkoutSessionStatus::Scheduled->value,
                WorkoutSessionStatus::InProgress->value,
                WorkoutSessionStatus::PartiallyCompleted->value,
            ])
            ->latest('id')
            ->first();

        if ($session !== null) {
            if ($session->status === WorkoutSessionStatus::Scheduled) {
                $session->update([
                    'status' => WorkoutSessionStatus::InProgress,
                    'started_at' => now(),
                ]);
            }

            return $session->fresh();
        }

        return AetherWorkoutSession::query()->create([
            'user_id' => $user->id,
            'aether_generated_program_id' => $program->id,
            'aether_program_workout_day_id' => $workoutDay->id,
            'scheduled_for' => now()->toDateString(),
            'started_at' => now(),
            'status' => WorkoutSessionStatus::InProgress,
        ]);
    }

    public function activeForDay(
        User $user,
        AetherGeneratedProgram $program,
        AetherProgramWorkoutDay $workoutDay,
    ): ?AetherWorkoutSession {
        return AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->where('aether_program_workout_day_id', $workoutDay->id)
            ->whereIn('status', [
                WorkoutSessionStatus::InProgress->value,
                WorkoutSessionStatus::PartiallyCompleted->value,
            ])
            ->latest('id')
            ->first();
    }

    public function complete(AetherWorkoutSession $session): AetherWorkoutSession
    {
        $session->update([
            'status' => WorkoutSessionStatus::Completed,
            'completed_at' => now(),
        ]);

        return $session->fresh();
    }
}
