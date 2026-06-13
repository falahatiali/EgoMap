<?php

namespace App\Services\Missions;

use App\Models\User;
use App\Services\Profile\UserAetherProgramHistoryService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Services\MissionEnrollmentFieldService;
use Modules\MissionEngine\Support\MissionEnrollmentPresenter;
use Modules\MissionEngine\Support\MissionProGate;

final class MissionCalibrationService
{
    public function __construct(
        private MissionAetherProgramService $aetherPrograms,
        private MissionWorkspacePresenter $workspacePresenter,
        private AetherProfileService $aetherProfile,
        private MissionEnrollmentFieldService $enrollmentFields,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function defaults(MissionEnrollment $enrollment, User $user): array
    {
        $this->assertOwned($enrollment, $user);

        $profile = $this->aetherProfile->forUser($user);

        return [
            'wizard' => $this->aetherPrograms->loadWizardDefaults($profile, $enrollment),
            'profile_complete' => $profile?->isQuestionnaireComplete() ?? false,
            'already_calibrated' => $this->isCalibrated($enrollment),
        ];
    }

    /**
     * @param  list<string>  $targets
     * @param  array<string, mixed>  $wizard
     * @return array<string, mixed>
     */
    public function complete(
        MissionEnrollment $enrollment,
        User $user,
        array $targets,
        array $wizard,
        string $locale,
        ?string $entryToolKey = null,
        bool $force = false,
    ): array {
        $this->assertOwned($enrollment, $user);
        $this->assertAetherMission($enrollment);

        if ($this->isCalibrated($enrollment) && ! $force) {
            $this->throwAlreadyCalibrated($enrollment, $user, $locale);
        }

        $this->assertTargetsAllowed($enrollment, $user, $targets, $locale);

        if (isset($wizard['gym_days']) && is_array($wizard['gym_days'])) {
            $this->enrollmentFields->merge($enrollment, [
                'gym_days' => array_values($wizard['gym_days']),
            ], $user);
        }

        $startedAt = microtime(true);
        $generatedTargets = [];

        DB::transaction(function () use ($enrollment, $user, $targets, $wizard, &$generatedTargets): void {
            foreach ($targets as $target) {
                $this->aetherPrograms->generate($user, $wizard, $enrollment, $target);
                $generatedTargets[] = $target;
            }
        });

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        return $this->workspacePresenter->presentActivation(
            $enrollment->fresh(),
            $user,
            $locale,
            $generatedTargets,
            $this->resolveFocusToolKey($entryToolKey, $generatedTargets),
            $durationMs,
        );
    }

    /**
     * @param  list<string>  $targets
     * @param  array<string, mixed>  $wizard
     * @return array<string, mixed>
     */
    public function regenerate(
        MissionEnrollment $enrollment,
        User $user,
        array $targets,
        array $wizard,
        string $locale,
        ?string $entryToolKey = null,
    ): array {
        return $this->complete(
            $enrollment,
            $user,
            $targets,
            $wizard,
            $locale,
            $entryToolKey,
            force: true,
        );
    }

    public function isCalibrated(MissionEnrollment $enrollment): bool
    {
        return AetherGeneratedProgram::query()
            ->where('mission_enrollment_id', $enrollment->id)
            ->exists();
    }

    /**
     * @param  list<string>  $targets
     */
    private function assertTargetsAllowed(
        MissionEnrollment $enrollment,
        User $user,
        array $targets,
        string $locale,
    ): void {
        abort_if($targets === [], 422, __('missions.calibration_targets_required', locale: $locale));

        $presenter = new MissionEnrollmentPresenter($enrollment);
        $capabilities = $presenter->enabledCapabilities($locale);
        $taskConfig = collect($capabilities)->firstWhere('key', 'task')['config'] ?? [];
        $nutritionConfig = collect($capabilities)->firstWhere('key', 'nutrition')['config'] ?? [];

        foreach ($targets as $target) {
            $allowed = match ($target) {
                'workout' => MissionProGate::canUseFeature($user, $taskConfig, 'ai_workout_plan'),
                'meal' => MissionProGate::canUseFeature($user, $nutritionConfig, 'ai_meal_plan'),
                default => false,
            };

            abort_unless($allowed, 403, __('missions.pro_hint', locale: $locale));
        }
    }

    private function assertOwned(MissionEnrollment $enrollment, User $user): void
    {
        abort_unless($enrollment->user_id === $user->id, 403);
    }

    private function assertAetherMission(MissionEnrollment $enrollment): void
    {
        $enrollment->loadMissing('template');
        $snapshot = is_array($enrollment->template_snapshot) ? $enrollment->template_snapshot : [];
        $meta = is_array($enrollment->template?->meta) ? $enrollment->template->meta : [];
        $engineModule = $snapshot['meta']['engine_module'] ?? $meta['engine_module'] ?? null;

        abort_unless($engineModule === 'aether', 422, 'This mission does not use AetherEngine.');
    }

    /**
     * @param  list<string>  $generatedTargets
     */
    private function resolveFocusToolKey(?string $entryToolKey, array $generatedTargets): string
    {
        if ($entryToolKey !== null && $entryToolKey !== '') {
            return $entryToolKey;
        }

        return in_array('workout', $generatedTargets, true) ? 'task' : 'nutrition';
    }

    private function throwAlreadyCalibrated(
        MissionEnrollment $enrollment,
        User $user,
        string $locale,
    ): never {
        throw new HttpResponseException(response()->json([
            'message' => __('missions.calibration_already_complete', locale: $locale),
            'code' => 'already_calibrated',
            'regenerate_api' => url('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/regenerate'),
            'engines' => $this->workspacePresenter->present($enrollment, $user, $locale)['engines'],
        ], 409));
    }
}
