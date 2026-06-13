<?php

namespace Modules\MissionEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Missions\MissionCalibrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AetherEngine\Enums\CookingAbility;
use Modules\AetherEngine\Enums\DietaryPattern;
use Modules\AetherEngine\Enums\EquipmentAccess;
use Modules\AetherEngine\Enums\Gender;
use Modules\AetherEngine\Enums\MotivationStyle;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Enums\SessionDuration;
use Modules\AetherEngine\Enums\TrainingStylePreference;
use Modules\AetherEngine\Enums\WorkoutTimePreference;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionApiService;
use Modules\MissionEngine\Services\MissionEnrollmentFieldService;
use Modules\MissionEngine\Services\MissionEnrollmentService;
use Modules\MissionEngine\Services\MissionSupplementLogService;

class MissionApiController extends Controller
{
    /**
     * GET /api/v1/missions
     */
    public function catalog(Request $request, MissionApiService $api): JsonResponse
    {
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->catalog($request->user('sanctum'), $locale));
    }

    /**
     * GET /api/v1/missions/{slug}
     */
    public function show(Request $request, string $slug, MissionApiService $api): JsonResponse
    {
        $template = MissionTemplate::query()->where('slug', $slug)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->templateDetail($template, $request->user('sanctum'), $locale));
    }

    /**
     * POST /api/v1/missions/{slug}/enroll
     */
    public function enroll(
        Request $request,
        string $slug,
        MissionEnrollmentService $enrollmentService,
        MissionApiService $api,
    ): JsonResponse {
        $user = $this->requireUser($request);
        $template = MissionTemplate::query()->where('slug', $slug)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $existing = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('template_id', $template->id)
            ->where('status', 'active')
            ->first();

        if ($existing !== null) {
            return response()->json([
                'enrollment' => $api->enrollmentWorkspace($existing, $user, $locale)['enrollment'],
                'already_enrolled' => true,
            ]);
        }

        $enrollment = $enrollmentService->enroll($user, $template, $validated['title'] ?? null);

        return response()->json([
            'enrollment' => $api->enrollmentWorkspace($enrollment, $user, $locale)['enrollment'],
            'already_enrolled' => false,
        ], 201);
    }

    /**
     * GET /api/v1/mission-enrollments
     */
    public function enrollments(Request $request, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->userEnrollments($user, $locale));
    }

    /**
     * GET /api/v1/mission-enrollments/{uuid}
     */
    public function enrollmentShow(Request $request, string $uuid, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->enrollmentWorkspace($enrollment, $user, $locale));
    }

    /**
     * PATCH /api/v1/mission-enrollments/{uuid}/fields
     */
    public function updateFields(
        Request $request,
        string $uuid,
        MissionEnrollmentFieldService $fields,
        MissionApiService $api,
    ): JsonResponse {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless($enrollment->user_id === $user->id, 403);

        $validated = $request->validate([
            'fields' => ['required', 'array'],
        ]);

        $fields->merge($enrollment, $validated['fields'], $user);
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->enrollmentWorkspace($enrollment->fresh(), $user, $locale));
    }

    /**
     * GET /api/v1/mission-enrollments/{uuid}/daily-reports?date=
     */
    public function showDailyReport(Request $request, string $uuid, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        return response()->json([
            'report' => $api->dailyReportForDate($enrollment, $user, $validated['date']),
        ]);
    }

    /**
     * POST /api/v1/mission-enrollments/{uuid}/daily-reports
     */
    public function saveDailyReport(Request $request, string $uuid, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'report_date' => ['required', 'date'],
            'body_weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'mood_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'energy_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sleep_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'trained_today' => ['nullable', 'boolean'],
            'nutrition_logged' => ['nullable', 'boolean'],
            'highlights' => ['nullable', 'string', 'max:2000'],
            'challenges' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return response()->json([
            'report' => $api->saveDailyReport($enrollment, $user, $validated),
        ]);
    }

    /**
     * POST /api/v1/mission-enrollments/{uuid}/supplements/products
     */
    public function addSupplementProduct(
        Request $request,
        string $uuid,
        MissionSupplementLogService $supplements,
        MissionApiService $api,
    ): JsonResponse {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless($enrollment->user_id === $user->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'default_unit' => ['nullable', 'string', 'max:32'],
            'default_amount' => ['nullable', 'string', 'max:64'],
        ]);

        $supplements->addProduct($enrollment, $validated);
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->enrollmentWorkspace($enrollment->fresh(), $user, $locale));
    }

    /**
     * POST /api/v1/mission-enrollments/{uuid}/supplements/intakes
     */
    public function logSupplementIntake(
        Request $request,
        string $uuid,
        MissionSupplementLogService $supplements,
        MissionApiService $api,
    ): JsonResponse {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless($enrollment->user_id === $user->id, 403);

        $validated = $request->validate([
            'intake_date' => ['required', 'date'],
            'supplement_product_id' => ['nullable', 'integer'],
            'product_name' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999'],
            'unit' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $supplements->logIntake($enrollment, $user, $validated);
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        return response()->json($api->enrollmentWorkspace($enrollment->fresh(), $user, $locale));
    }

    /**
     * GET /api/v1/mission-enrollments/{uuid}/calibration/defaults
     */
    public function calibrationDefaults(Request $request, string $uuid, MissionCalibrationService $calibration): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json($calibration->defaults($enrollment, $user));
    }

    /**
     * POST /api/v1/mission-enrollments/{uuid}/calibration/complete
     */
    public function calibrationComplete(Request $request, string $uuid, MissionCalibrationService $calibration, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        $validated = $this->validateCalibrationPayload($request);

        return response()->json(
            $calibration->complete(
                $enrollment,
                $user,
                $validated['targets'],
                $validated['wizard'],
                $locale,
                $validated['intent']['entry_tool_key'] ?? null,
            ),
            201,
        );
    }

    /**
     * POST /api/v1/mission-enrollments/{uuid}/calibration/regenerate
     */
    public function calibrationRegenerate(Request $request, string $uuid, MissionCalibrationService $calibration, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        $validated = $this->validateCalibrationPayload($request);
        abort_unless((bool) ($validated['force'] ?? false), 422, 'force must be true to regenerate.');

        return response()->json(
            $calibration->regenerate(
                $enrollment,
                $user,
                $validated['targets'],
                $validated['wizard'],
                $locale,
                $validated['intent']['entry_tool_key'] ?? null,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCalibrationPayload(Request $request): array
    {
        return $request->validate([
            'intent' => ['nullable', 'array'],
            'intent.entry_tool_key' => ['nullable', 'string', 'max:32'],
            'intent.locale' => ['nullable', 'string', 'max:8'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*' => ['required', 'string', Rule::in(['workout', 'meal'])],
            'wizard' => ['required', 'array'],
            'wizard.age' => ['required', 'integer', 'min:14', 'max:90'],
            'wizard.gender' => ['required', 'string', Rule::in(array_map(fn (Gender $g): string => $g->value, Gender::cases()))],
            'wizard.height_cm' => ['required', 'integer', 'min:120', 'max:230'],
            'wizard.weight_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'wizard.body_fat_percent' => ['nullable', 'numeric', 'min:3', 'max:60'],
            'wizard.primary_goal' => ['required', 'string', Rule::in(array_map(fn (PrimaryGoal $g): string => $g->value, PrimaryGoal::cases()))],
            'wizard.training_days_per_week' => ['required', 'integer', 'min:2', 'max:6'],
            'wizard.session_duration' => ['required', 'string', Rule::in(array_map(fn (SessionDuration $d): string => $d->value, SessionDuration::cases()))],
            'wizard.preferred_workout_time' => ['nullable', 'string', Rule::in(array_map(fn (WorkoutTimePreference $t): string => $t->value, WorkoutTimePreference::cases()))],
            'wizard.gym_days' => ['nullable', 'array'],
            'wizard.gym_days.*' => ['string', Rule::in(['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'])],
            'wizard.equipment' => ['required', 'string', Rule::in(array_map(fn (EquipmentAccess $e): string => $e->value, EquipmentAccess::cases()))],
            'wizard.injury_tags' => ['nullable', 'array'],
            'wizard.injury_tags.*' => ['string', Rule::in(['knee', 'lower_back', 'shoulder', 'wrist'])],
            'wizard.dietary_pattern' => ['required', 'string', Rule::in(array_map(fn (DietaryPattern $d): string => $d->value, DietaryPattern::cases()))],
            'wizard.cooking_ability' => ['nullable', 'string', Rule::in(array_map(fn (CookingAbility $c): string => $c->value, CookingAbility::cases()))],
            'wizard.coaching_tone' => ['nullable', 'string'],
            'wizard.motivation_style' => ['required', 'string', Rule::in(array_map(fn (MotivationStyle $m): string => $m->value, MotivationStyle::cases()))],
            'wizard.training_style' => ['required', 'string', Rule::in(array_map(fn (TrainingStylePreference $s): string => $s->value, TrainingStylePreference::cases()))],
            'commitment' => ['required', 'array'],
            'commitment.confirmed' => ['required', 'boolean', 'accepted'],
            'force' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * POST /api/v1/mission-enrollments/{uuid}/programs/generate
     */
    public function generateProgram(Request $request, string $uuid, MissionCalibrationService $calibration, MissionApiService $api): JsonResponse
    {
        $user = $this->requireUser($request);
        $enrollment = MissionEnrollment::query()->where('uuid', $uuid)->firstOrFail();
        $locale = $api->resolveLocale($request->header('Accept-Language'));

        $validated = $request->validate([
            'applied_target' => ['required', Rule::in(['workout', 'meal'])],
            'wizard' => ['required', 'array'],
            'wizard.age' => ['required', 'integer', 'min:14', 'max:90'],
            'wizard.gender' => ['required', 'string', Rule::in(array_map(fn (Gender $g): string => $g->value, Gender::cases()))],
            'wizard.height_cm' => ['required', 'integer', 'min:120', 'max:230'],
            'wizard.weight_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'wizard.body_fat_percent' => ['nullable', 'numeric', 'min:3', 'max:60'],
            'wizard.primary_goal' => ['required', 'string', Rule::in(array_map(fn (PrimaryGoal $g): string => $g->value, PrimaryGoal::cases()))],
            'wizard.training_days_per_week' => ['required', 'integer', 'min:2', 'max:6'],
            'wizard.session_duration' => ['required', 'string', Rule::in(array_map(fn (SessionDuration $d): string => $d->value, SessionDuration::cases()))],
            'wizard.preferred_workout_time' => ['nullable', 'string', Rule::in(array_map(fn (WorkoutTimePreference $t): string => $t->value, WorkoutTimePreference::cases()))],
            'wizard.equipment' => ['required', 'string', Rule::in(array_map(fn (EquipmentAccess $e): string => $e->value, EquipmentAccess::cases()))],
            'wizard.injury_tags' => ['nullable', 'array'],
            'wizard.injury_tags.*' => ['string', Rule::in(['knee', 'lower_back', 'shoulder', 'wrist'])],
            'wizard.dietary_pattern' => ['required', 'string', Rule::in(array_map(fn (DietaryPattern $d): string => $d->value, DietaryPattern::cases()))],
            'wizard.cooking_ability' => ['nullable', 'string', Rule::in(array_map(fn (CookingAbility $c): string => $c->value, CookingAbility::cases()))],
            'wizard.coaching_tone' => ['nullable', 'string'],
            'wizard.motivation_style' => ['required', 'string', Rule::in(array_map(fn (MotivationStyle $m): string => $m->value, MotivationStyle::cases()))],
            'wizard.training_style' => ['required', 'string', Rule::in(array_map(fn (TrainingStylePreference $s): string => $s->value, TrainingStylePreference::cases()))],
        ]);

        return response()->json(
            $calibration->complete(
                $enrollment,
                $user,
                [$validated['applied_target']],
                $validated['wizard'],
                $locale,
            ),
            201,
        );
    }

    private function requireUser(Request $request): User
    {
        $user = $request->user('sanctum');
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
