<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionWorkoutExercise;
use Modules\MissionEngine\Models\MissionWorkoutSession;

final class MissionWorkoutLogService
{
    /**
     * @param  array{
     *     session_date: string,
     *     day_key?: string|null,
     *     focus?: string|null,
     *     duration_minutes?: int|null,
     *     notes?: string|null,
     *     exercises: list<array{
     *         name: string,
     *         notes?: string|null,
     *         sets: list<array{reps?: int|null, weight?: float|null, weight_unit?: string|null, notes?: string|null}>
     *     }>
     * }  $data
     */
    public function saveSession(MissionEnrollment $enrollment, User $user, array $data): MissionWorkoutSession
    {
        return DB::transaction(function () use ($enrollment, $user, $data): MissionWorkoutSession {
            $sessionDate = Carbon::parse($data['session_date'])->toDateString();

            $session = MissionWorkoutSession::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('session_date', $sessionDate)
                ->first();

            if ($session === null) {
                $session = MissionWorkoutSession::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'session_date' => $sessionDate,
                    'day_key' => $data['day_key'] ?? null,
                    'focus' => $data['focus'] ?? null,
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
            } else {
                $session->update([
                    'day_key' => $data['day_key'] ?? null,
                    'focus' => $data['focus'] ?? null,
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $session->exercises()->each(fn (MissionWorkoutExercise $exercise) => $exercise->sets()->delete());
            $session->exercises()->delete();

            foreach ($data['exercises'] as $index => $exerciseData) {
                $name = trim((string) ($exerciseData['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $exercise = $session->exercises()->create([
                    'name' => $name,
                    'sort_order' => ($index + 1) * 10,
                    'notes' => $exerciseData['notes'] ?? null,
                ]);

                foreach ($exerciseData['sets'] ?? [] as $setIndex => $setData) {
                    $reps = $setData['reps'] ?? null;

                    if ($reps === null && ($setData['weight'] ?? null) === null) {
                        continue;
                    }

                    $exercise->sets()->create([
                        'set_number' => $setIndex + 1,
                        'reps' => $reps,
                        'weight' => $setData['weight'] ?? null,
                        'weight_unit' => $setData['weight_unit'] ?? 'kg',
                        'notes' => $setData['notes'] ?? null,
                    ]);
                }
            }

            $enrollment->touchActivity();

            MissionActivityLog::query()->create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'event_type' => MissionActivityEvent::WorkoutLogged,
                'payload' => [
                    'session_uuid' => $session->uuid,
                    'session_date' => $sessionDate,
                    'exercise_count' => $session->exercises()->count(),
                ],
                'logged_at' => now(),
            ]);

            return $session->load(['exercises.sets']);
        });
    }

    /**
     * @return LengthAwarePaginator<int, MissionWorkoutSession>
     */
    public function paginateSessions(MissionEnrollment $enrollment, int $perPage = 10, string $pageName = 'workoutPage'): LengthAwarePaginator
    {
        return MissionWorkoutSession::query()
            ->where('enrollment_id', $enrollment->id)
            ->with(['exercises.sets'])
            ->orderByDesc('session_date')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function findSessionForDate(MissionEnrollment $enrollment, string $date): ?MissionWorkoutSession
    {
        return MissionWorkoutSession::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('session_date', Carbon::parse($date)->toDateString())
            ->with(['exercises.sets'])
            ->first();
    }
}
