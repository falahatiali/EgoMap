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

    private function requireUser(Request $request): User
    {
        $user = $request->user('sanctum');
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
