<?php

namespace Modules\VirtueEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\VirtueEngine\Services\VirtueApiService;
use Modules\VirtueEngine\Services\VirtueHabitService;
use Modules\VirtueEngine\Services\VirtueProgressService;

class VirtueApiController extends Controller
{
    public function __construct(
        private readonly VirtueHabitService $habits,
        private readonly VirtueProgressService $progress,
        private readonly VirtueApiService $api,
    ) {}

    /** GET /api/v1/virtue/habits — list predefined habits */
    public function habits(): JsonResponse
    {
        $habits = $this->habits->listPredefinedHabits()
            ->map(fn ($h) => $this->api->habitPayload($h))
            ->values();

        return response()->json(['data' => $habits]);
    }

    /**
     * POST /api/v1/virtue/habits/analyze — AI-analyse a custom habit description
     *
     * @param  array{description: string}  $validated
     */
    public function analyzeHabit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $habit = $this->habits->analyzeAndStoreCustomHabit($validated['description']);

        return response()->json(['data' => $this->api->habitPayload($habit)], 201);
    }

    /** GET /api/v1/virtue/routines — list user's routines */
    public function routines(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status');

        $routines = $this->habits->userRoutines($user, is_string($status) ? $status : null)
            ->map(fn ($r) => $this->api->routinePayload($r))
            ->values();

        return response()->json(['data' => $routines]);
    }

    /**
     * POST /api/v1/virtue/routines — start a new routine
     *
     * @param  array{virtue_habit_id: int, personal_note?: string|null, goal_type?: string, goal_target?: int}  $validated
     */
    public function startRoutine(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'virtue_habit_id' => ['required', 'integer', 'exists:virtue_habits,id'],
            'personal_note' => ['nullable', 'string', 'max:500'],
            'goal_type' => ['nullable', 'string', 'in:days_count,success_count'],
            'goal_target' => ['nullable', 'integer', 'min:7', 'max:365'],
        ]);

        $routine = $this->habits->startRoutine($request->user(), $validated);

        return response()->json(['data' => $this->api->routinePayload($routine->load('habit'))], 201);
    }

    /** GET /api/v1/virtue/routines/{routineId} — progress details */
    public function routineProgress(Request $request, int $routineId): JsonResponse
    {
        $routine = $this->habits->findRoutineForUser($routineId, $request->user());

        return response()->json(['data' => $this->api->routineProgressPayload($routine)]);
    }

    /**
     * POST /api/v1/virtue/routines/{routineId}/success — log a win
     *
     * @param  array{situation?: string|null, emotional_state?: string|null}  $validated
     */
    public function logSuccess(Request $request, int $routineId): JsonResponse
    {
        $routine = $this->habits->findRoutineForUser($routineId, $request->user());

        $validated = $request->validate([
            'situation' => ['nullable', 'string', 'max:1000'],
            'emotional_state' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->progress->logSuccess($routine, $request->user(), $validated);

        return response()->json([
            'data' => [
                'success_log' => $this->api->successLogPayload($result['success_log']),
                'routine' => $this->api->routinePayload($routine->refresh()->load('habit')),
                'gamification' => $result['gamification'],
                'routine_completed' => $result['routine_completed'],
            ],
        ]);
    }

    /**
     * POST /api/v1/virtue/routines/{routineId}/slip — log a slip
     *
     * @param  array{what_happened?: string|null, choose_punishment_id?: int|null}  $validated
     */
    public function logSlip(Request $request, int $routineId): JsonResponse
    {
        $routine = $this->habits->findRoutineForUser($routineId, $request->user());

        $validated = $request->validate([
            'what_happened' => ['nullable', 'string', 'max:1000'],
            'choose_punishment_id' => ['nullable', 'integer'],
        ]);

        $result = $this->progress->logSlip($routine, $request->user(), $validated);

        return response()->json([
            'data' => [
                'slip_log' => [
                    'id' => $result['slip_log']->id,
                    'what_happened' => $result['slip_log']->what_happened,
                    'logged_at' => $result['slip_log']->logged_at->toIso8601String(),
                ],
                'routine' => $this->api->routinePayload($routine->refresh()->load('habit')),
                'gamification' => $result['gamification'],
                'ai_response' => $result['ai_response'],
                'punishment_suggestions' => $result['punishment_suggestions'],
            ],
        ]);
    }

    /** POST /api/v1/virtue/routines/{routineId}/complete — celebrate completion */
    public function completeRoutine(Request $request, int $routineId): JsonResponse
    {
        $routine = $this->habits->findRoutineForUser($routineId, $request->user());

        $result = $this->progress->completeRoutine($routine, $request->user());

        return response()->json([
            'data' => [
                'routine' => $this->api->routinePayload($routine->refresh()->load('habit')),
                'gamification' => $result['gamification'],
            ],
        ]);
    }
}
