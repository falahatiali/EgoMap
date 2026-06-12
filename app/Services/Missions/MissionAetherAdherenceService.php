<?php

namespace App\Services\Missions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherWorkoutSession;
use Modules\AetherEngine\Services\AetherWorkoutLogService;
use Modules\MissionEngine\Models\MissionEnrollment;

class MissionAetherAdherenceService
{
    public function __construct(private AetherWorkoutLogService $workoutLogs) {}

    /**
     * @return Collection<int, AetherGeneratedProgram>
     */
    public function programsForEnrollment(MissionEnrollment $enrollment): Collection
    {
        return AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->where('mission_enrollment_id', $enrollment->id)
            ->latest('id')
            ->get();
    }

    public function latestProgramForEnrollment(MissionEnrollment $enrollment, string $target): ?AetherGeneratedProgram
    {
        return AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->where('mission_enrollment_id', $enrollment->id)
            ->where('applied_target', $target)
            ->latest('id')
            ->first();
    }

    public function workoutAdherencePercent(User $user, ?AetherGeneratedProgram $program): int
    {
        if ($program === null) {
            return 0;
        }

        return $this->workoutLogs->weekCompletionPercent($user, $program);
    }

    public function trainedOnDate(User $user, string $date): bool
    {
        return AetherWorkoutSession::query()
            ->where('user_id', $user->id)
            ->whereDate('scheduled_for', $date)
            ->whereIn('status', ['in_progress', 'partially_completed', 'completed'])
            ->exists();
    }

    public function syncEnrollmentProgress(MissionEnrollment $enrollment, User $user): void
    {
        $programs = $this->programsForEnrollment($enrollment);

        if ($programs->isEmpty()) {
            return;
        }

        $scores = $programs->map(function (AetherGeneratedProgram $program) use ($user): int {
            if ($program->applied_target === 'workout') {
                return $this->workoutAdherencePercent($user, $program);
            }

            return $program->metabolic_target_calories !== null ? 50 : 0;
        });

        $enrollment->update([
            'progress_percent' => round($scores->avg(), 2),
        ]);
    }
}
