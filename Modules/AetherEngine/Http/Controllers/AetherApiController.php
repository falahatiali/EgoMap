<?php

namespace Modules\AetherEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramExerciseSet;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;
use Modules\AetherEngine\Services\AetherApiService;

class AetherApiController extends Controller
{
    /**
     * GET /api/v1/aether/programs
     */
    public function programs(Request $request, AetherApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        $validated = $request->validate([
            'mission_enrollment_uuid' => ['nullable', 'uuid'],
        ]);

        return response()->json(
            $api->programsForUser(
                $user,
                $locale,
                $validated['mission_enrollment_uuid'] ?? null,
            ),
        );
    }

    /**
     * GET /api/v1/aether/programs/{uuid}
     */
    public function programShow(Request $request, string $uuid, AetherApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $program = AetherGeneratedProgram::query()->where('uuid', $uuid)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->programDetail($user, $program, $locale));
    }

    /**
     * POST /api/v1/aether/programs/{uuid}/workout-days/{dayId}/sets/{setId}/toggle
     */
    public function toggleWorkoutSet(
        Request $request,
        string $uuid,
        int $dayId,
        int $setId,
        AetherApiService $api,
    ): JsonResponse {
        $user = $this->requireUser($request);
        $program = AetherGeneratedProgram::query()->where('uuid', $uuid)->firstOrFail();
        $workoutDay = AetherProgramWorkoutDay::query()
            ->where('id', $dayId)
            ->where('aether_generated_program_id', $program->id)
            ->firstOrFail();
        $exerciseSet = AetherProgramExerciseSet::query()
            ->with('programExercise')
            ->findOrFail($setId);

        return response()->json(
            $api->toggleWorkoutSet($user, $program, $exerciseSet, $workoutDay),
        );
    }

    /**
     * GET /api/v1/aether/programs/{uuid}/volume-chart?days=30
     */
    public function volumeChart(Request $request, string $uuid, AetherApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:7', 'max:90'],
        ]);

        $program = AetherGeneratedProgram::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json($api->volumeChart($user, $program, (int) ($validated['days'] ?? 30)));
    }

    /**
     * GET /api/v1/aether/programs/{uuid}/check-in/status
     */
    public function checkInStatus(Request $request, string $uuid, AetherApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $program = AetherGeneratedProgram::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json($api->checkInStatus($user, $program));
    }

    /**
     * POST /api/v1/aether/programs/{uuid}/check-in
     */
    public function submitCheckIn(Request $request, string $uuid, AetherApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);

        $validated = $request->validate([
            'sessions_completed' => ['required', 'integer', 'min:0', 'max:7'],
            'intensity_rating' => ['required', 'integer', 'in:1,2,3'],
            'had_pain' => ['required', 'boolean'],
            'pain_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $program = AetherGeneratedProgram::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json(
            $api->submitCheckIn($user, $program, $validated),
        );
    }

    /**
     * POST /api/v1/aether/programs/{uuid}/workout-days/{dayId}/sets/{setId}/weight
     */
    public function logSetWeight(
        Request $request,
        string $uuid,
        int $dayId,
        int $setId,
        AetherApiService $api,
    ): JsonResponse {
        $user = $this->requireUser($request);

        $validated = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        $program = AetherGeneratedProgram::query()->where('uuid', $uuid)->firstOrFail();
        $workoutDay = AetherProgramWorkoutDay::query()
            ->where('id', $dayId)
            ->where('aether_generated_program_id', $program->id)
            ->firstOrFail();
        $exerciseSet = AetherProgramExerciseSet::query()
            ->with('programExercise')
            ->findOrFail($setId);

        return response()->json(
            $api->logSetWeight($user, $program, $exerciseSet, $workoutDay, (float) $validated['weight_kg']),
        );
    }

    private function requireUser(Request $request): User
    {
        $user = $request->user('sanctum');
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
