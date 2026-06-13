<?php

namespace App\Services\Missions;

use App\Models\User;
use App\Services\Profile\UserAetherProgramHistoryService;
use Illuminate\Support\Str;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherUserProfile;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Services\MissionSupplementLogService;
use Modules\MissionEngine\Support\MissionEnrollmentPresenter;
use Modules\MissionEngine\Support\MissionLocalizedText;
use Modules\MissionEngine\Support\MissionProGate;

final class MissionWorkspacePresenter
{
    public const API_VERSION = '2026-06-12.v1';

    private const CALIBRATION_TOTAL_STEPS = 8;

    private const ACTIVATION_MILESTONE_PERCENT = 8.0;

    /**
     * @var array<string, array{label_key: string, icon: string}>
     */
    private const TOOL_DISPLAY = [
        'task' => ['label_key' => 'missions.tab_workout', 'icon' => 'fa-dumbbell'],
        'nutrition' => ['label_key' => 'missions.tab_nutrition', 'icon' => 'fa-utensils'],
        'supplement' => ['label_key' => 'missions.tab_supplements', 'icon' => 'fa-capsules'],
        'measurement' => ['label_key' => 'missions.tab_daily', 'icon' => 'fa-calendar-day'],
        'schedule' => ['label_key' => 'missions.tab_schedule', 'icon' => 'fa-calendar-week'],
        'equipment' => ['label_key' => 'missions.tab_equipment', 'icon' => 'fa-bag-shopping'],
        'registration' => ['label_key' => 'missions.tab_registration', 'icon' => 'fa-clipboard-check'],
        'mindset' => ['label_key' => 'missions.tab_mindset', 'icon' => 'fa-brain'],
        'content' => ['label_key' => 'missions.tab_content', 'icon' => 'fa-book-open'],
        'checklist' => ['label_key' => 'missions.tab_checklist', 'icon' => 'fa-square-check'],
        'finance' => ['label_key' => 'missions.tab_finance', 'icon' => 'fa-wallet'],
    ];

    public function __construct(
        private MissionAetherAdherenceService $adherence,
        private AetherProfileService $aetherProfile,
        private UserAetherProgramHistoryService $programHistory,
        private MissionSupplementLogService $supplements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(MissionEnrollment $enrollment, User $user, string $locale): array
    {
        $enrollment->loadMissing('template');
        $engineModule = $this->resolveEngineModule($enrollment);

        $workoutProgram = $this->adherence->latestProgramForEnrollment($enrollment, 'workout');
        $mealProgram = $this->adherence->latestProgramForEnrollment($enrollment, 'meal');
        $profile = $this->aetherProfile->forUser($user);
        $aetherContext = $this->resolveAetherContext(
            $enrollment,
            $user,
            $locale,
            $engineModule,
            $workoutProgram,
            $mealProgram,
            $profile,
        );

        $workspaceMode = $this->resolveWorkspaceMode($engineModule, $aetherContext);
        $tools = $this->presentTools(
            $enrollment,
            $user,
            $locale,
            $engineModule,
            $aetherContext,
            $workoutProgram,
            $mealProgram,
        );

        return [
            'meta' => $this->presentMeta(),
            'labels' => $this->presentLabels($locale),
            'mission' => $this->presentMission($enrollment, $locale, $engineModule),
            'enrollment' => $this->presentEnrollment($enrollment, $locale),
            'engines' => $this->presentEngines($engineModule, $aetherContext, $workoutProgram, $mealProgram, $profile, $user, $locale),
            'workspace' => $this->presentWorkspace($workspaceMode, $aetherContext, $enrollment, $locale, $tools),
            'tools' => $tools,
        ];
    }

    /**
     * @param  list<string>  $generatedTargets
     * @return array<string, mixed>
     */
    public function presentActivation(
        MissionEnrollment $enrollment,
        User $user,
        string $locale,
        array $generatedTargets,
        ?string $focusToolKey = null,
        ?int $generationDurationMs = null,
    ): array {
        $payload = $this->present($enrollment->fresh(), $user, $locale);
        $previousMode = 'locked';
        $focusToolKey ??= in_array('workout', $generatedTargets, true) ? 'task' : 'nutrition';
        $workoutProgram = $payload['engines']['aether']['programs']['workout'] ?? null;

        $payload['meta']['generation'] = [
            'duration_ms' => $generationDurationMs,
            'engine' => 'aether',
            'provider' => 'rules-engine',
        ];

        $payload['activation'] = [
            'status' => 'completed',
            'previous_mode' => $previousMode,
            'current_mode' => 'active',
            'focus_tool_key' => $focusToolKey,
            'milestone' => [
                'type' => 'aether_calibrated',
                'progress_percent_awarded' => self::ACTIVATION_MILESTONE_PERCENT,
                'enrollment_progress_percent' => (float) $enrollment->fresh()->progress_percent,
            ],
        ];

        $payload['reveal'] = [
            'headline' => __('missions.aether_reveal_headline', locale: $locale),
            'subheadline' => __('missions.aether_reveal_subheadline', locale: $locale),
            'primary_cta' => [
                'action' => 'open_aether_program',
                'label' => __('missions.aether_reveal_open_week', locale: $locale),
                'program_uuid' => is_array($workoutProgram) ? ($workoutProgram['uuid'] ?? null) : null,
                'tool_key' => $focusToolKey,
            ],
            'phase_timeline' => $this->presentPhaseTimeline($enrollment, $locale),
        ];

        $payload['workspace_refresh'] = [
            'method' => 'GET',
            'url' => url('/api/v1/mission-enrollments/'.$enrollment->uuid),
        ];

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMeta(): array
    {
        return [
            'api_version' => self::API_VERSION,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function presentLabels(string $locale): array
    {
        return [
            'workspace_title' => __('missions.workspace_title', locale: $locale),
            'workspace_subtitle' => __('missions.workspace_subtitle', locale: $locale),
            'activate_mission' => __('missions.activate_mission', locale: $locale),
            'calibration_cta' => __('missions.calibration_cta', locale: $locale),
            'continue_calibration' => __('missions.continue_calibration', locale: $locale),
            'locked_reason_aether' => __('missions.locked_reason_aether', locale: $locale),
            'locked_reason_pro' => __('missions.locked_reason_pro', locale: $locale),
            'locked_reason_mission_setup' => __('missions.locked_reason_mission_setup', locale: $locale),
        ];
    }

    private function resolveEngineModule(MissionEnrollment $enrollment): ?string
    {
        $snapshot = is_array($enrollment->template_snapshot) ? $enrollment->template_snapshot : [];
        $meta = is_array($enrollment->template?->meta) ? $enrollment->template->meta : [];

        return $snapshot['meta']['engine_module'] ?? $meta['engine_module'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMission(MissionEnrollment $enrollment, string $locale, ?string $engineModule): array
    {
        $presenter = new MissionEnrollmentPresenter($enrollment);
        $snapshot = is_array($enrollment->template_snapshot) ? $enrollment->template_snapshot : [];
        $meta = is_array($enrollment->template?->meta) ? $enrollment->template->meta : [];
        $currentPhase = collect($snapshot['phases'] ?? [])
            ->firstWhere('id', $enrollment->current_phase_id);

        $phaseTitle = null;
        $phaseSlug = null;
        $phaseDurationDays = null;

        if (is_array($currentPhase)) {
            $phaseTitle = MissionLocalizedText::forLocale($currentPhase['title'] ?? '', $locale);
            $phaseSlug = $currentPhase['slug'] ?? null;
            $phaseDurationDays = $currentPhase['duration_days'] ?? null;
        }

        return [
            'slug' => $snapshot['slug'] ?? $enrollment->template?->slug,
            'title' => $presenter->title($locale),
            'icon' => $snapshot['icon'] ?? 'fa-flag',
            'accent' => (string) ($meta['accent'] ?? $snapshot['meta']['accent'] ?? 'emerald'),
            'estimated_days' => $snapshot['estimated_days'] ?? null,
            'engine_module' => $engineModule,
            'current_phase' => $phaseTitle !== null && $phaseTitle !== '' ? [
                'slug' => $phaseSlug,
                'title' => $phaseTitle,
                'week_index' => 1,
                'duration_days' => $phaseDurationDays,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentEnrollment(MissionEnrollment $enrollment, string $locale): array
    {
        $presenter = new MissionEnrollmentPresenter($enrollment);

        return [
            'uuid' => $enrollment->uuid,
            'status' => $enrollment->status->value,
            'progress_percent' => (float) $enrollment->progress_percent,
            'started_at' => $enrollment->started_at?->toIso8601String(),
            'last_activity_at' => $enrollment->last_activity_at?->toIso8601String(),
            'field_values' => $presenter->fieldValues(),
        ];
    }

    /**
     * @param  array<string, mixed>  $aetherContext
     * @return array<string, mixed>
     */
    private function presentEngines(
        ?string $engineModule,
        array $aetherContext,
        ?AetherGeneratedProgram $workoutProgram,
        ?AetherGeneratedProgram $mealProgram,
        ?AetherUserProfile $profile,
        User $user,
        string $locale,
    ): array {
        if ($engineModule !== 'aether') {
            return [];
        }

        return [
            'aether' => [
                'module' => 'aether',
                'display_name' => 'AetherEngine',
                'status' => $aetherContext['status'],
                'status_reason' => $aetherContext['status_reason'],
                'calibration' => $aetherContext['calibration'],
                'access' => $aetherContext['access'],
                'programs' => [
                    'workout' => $workoutProgram !== null
                        ? $this->mapLinkedProgram($workoutProgram, $locale, $user)
                        : null,
                    'meal' => $mealProgram !== null
                        ? $this->mapLinkedProgram($mealProgram, $locale, $user)
                        : null,
                ],
                'profile' => $this->presentAetherProfile($profile, $locale),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $aetherContext
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    private function presentWorkspace(
        string $workspaceMode,
        array $aetherContext,
        MissionEnrollment $enrollment,
        string $locale,
        array $tools,
    ): array {
        $snapshot = is_array($enrollment->template_snapshot) ? $enrollment->template_snapshot : [];
        $currentPhase = collect($snapshot['phases'] ?? [])
            ->firstWhere('id', $enrollment->current_phase_id);
        $phaseTitle = is_array($currentPhase)
            ? MissionLocalizedText::forLocale($currentPhase['title'] ?? '', $locale)
            : null;

        return [
            'mode' => $workspaceMode,
            'primary_cta' => $this->presentPrimaryCta($workspaceMode, $aetherContext, $tools, $locale),
            'hero' => [
                'headline' => filled($phaseTitle)
                    ? $phaseTitle.' · '.__('missions.week_one', locale: $locale)
                    : __('missions.workspace_title', locale: $locale),
                'subheadline' => $workspaceMode === 'active'
                    ? __('missions.aether_hero_active', locale: $locale)
                    : __('missions.aether_hero_locked', locale: $locale),
                'progress_label' => $workspaceMode === 'active'
                    ? self::ACTIVATION_MILESTONE_PERCENT.'% · '.__('missions.activation_milestone', locale: $locale)
                    : __('missions.not_activated_yet', locale: $locale),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $aetherContext
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    private function presentPrimaryCta(
        string $workspaceMode,
        array $aetherContext,
        array $tools,
        string $locale,
    ): array {
        if ($workspaceMode === 'active') {
            $workoutTool = collect($tools)->firstWhere('key', 'task');

            return [
                'action' => 'open_tool',
                'label' => __('missions.start_week_one_workout', locale: $locale),
                'tool_key' => 'task',
                'program_uuid' => data_get($workoutTool, 'deep_link.program_uuid'),
            ];
        }

        if (($aetherContext['access']['user_has_pro'] ?? false) === false
            && ($aetherContext['status'] ?? '') === 'pro_required') {
            return [
                'action' => 'upgrade_pro',
                'label' => __('missions.upgrade_for_aether', locale: $locale),
                'engine_module' => 'aether',
            ];
        }

        return [
            'action' => 'start_aether_calibration',
            'label' => __('missions.activate_mission', locale: $locale),
            'engine_module' => 'aether',
        ];
    }

    /**
     * @param  array<string, mixed>  $aetherContext
     * @return list<array<string, mixed>>
     */
    private function presentTools(
        MissionEnrollment $enrollment,
        User $user,
        string $locale,
        ?string $engineModule,
        array $aetherContext,
        ?AetherGeneratedProgram $workoutProgram,
        ?AetherGeneratedProgram $mealProgram,
    ): array {
        $presenter = new MissionEnrollmentPresenter($enrollment);
        $capabilities = $presenter->enabledCapabilities($locale);
        $fieldValues = $presenter->fieldValues();
        $tools = [];

        foreach ($capabilities as $index => $capability) {
            $key = $capability['key'];
            $display = self::TOOL_DISPLAY[$key] ?? [
                'label_key' => null,
                'icon' => 'fa-circle',
            ];

            $tool = [
                'key' => $key,
                'capability_key' => $key,
                'label' => $display['label_key'] !== null
                    ? __($display['label_key'], locale: $locale)
                    : $capability['label'],
                'icon' => $display['icon'],
                'sort_order' => $index + 1,
                'status' => 'active',
                'lock' => null,
                'powered_by' => null,
                'snippet' => null,
                'insight' => null,
                'deep_link' => [
                    'type' => 'mission_tool',
                    'tool_key' => $key,
                ],
            ];

            if ($engineModule === 'aether' && $this->isAetherPoweredTool($key)) {
                $tool['powered_by'] = [
                    'engine_module' => 'aether',
                    'feature' => $key === 'task' ? 'ai_workout_plan' : 'ai_meal_plan',
                ];

                $program = $key === 'task' ? $workoutProgram : $mealProgram;
                $featureKey = $key === 'task' ? 'ai_workout_plan' : 'ai_meal_plan';
                $config = $capability['config'] ?? [];

                if (! MissionProGate::canUseFeature($user, $config, $featureKey) && $program === null) {
                    $tool['status'] = 'locked';
                    $tool['lock'] = [
                        'reason' => 'pro_required',
                        'message' => $key === 'task'
                            ? __('missions.unlock_workout', locale: $locale)
                            : __('missions.unlock_nutrition', locale: $locale),
                        'action' => 'upgrade_pro',
                    ];
                    $tool['deep_link'] = null;
                } elseif ($program === null) {
                    $tool['status'] = 'locked';
                    $tool['lock'] = [
                        'reason' => 'aether_calibration_required',
                        'message' => $key === 'task'
                            ? __('missions.unlock_workout', locale: $locale)
                            : __('missions.unlock_nutrition', locale: $locale),
                        'action' => 'start_aether_calibration',
                    ];
                    $tool['deep_link'] = null;
                } else {
                    $tool = array_merge($tool, $this->presentActiveAetherTool($program, $user, $locale, $key));
                }
            } else {
                $tool['snippet'] = $this->presentMissionToolSnippet($key, $capability, $fieldValues, $enrollment, $locale);
            }

            $tools[] = $tool;
        }

        return $tools;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentActiveAetherTool(
        AetherGeneratedProgram $program,
        User $user,
        string $locale,
        string $toolKey,
    ): array {
        $program->loadMissing(['workoutDays.exercises', 'nutritionDays.meals']);

        if ($toolKey === 'task') {
            $day = $program->workoutDays->sortBy('day_index')->first();
            $exerciseCount = $day?->exercises->count() ?? 0;

            return [
                'status' => 'active',
                'lock' => null,
                'snippet' => [
                    'type' => 'aether_program',
                    'headline' => $program->split?->value
                        ? Str::headline(str_replace('_', ' ', $program->split->value))
                        : $this->programHistory->summaryForProgram($program, $locale),
                    'detail' => $day !== null
                        ? __('missions.workout_day_preview', [
                            'day' => $day->label,
                            'count' => $exerciseCount,
                        ], locale: $locale)
                        : $this->programHistory->summaryForProgram($program, $locale),
                    'metric' => [
                        'label' => __('missions.adherence_label', locale: $locale),
                        'value' => $this->adherence->workoutAdherencePercent($user, $program).'%',
                    ],
                ],
                'insight' => filled($program->coach_week_focus) ? [
                    'source' => 'aether',
                    'type' => 'coach_tip',
                    'text' => (string) $program->coach_week_focus,
                    'priority' => 'normal',
                    'expires_at' => null,
                ] : null,
                'deep_link' => [
                    'type' => 'aether_program',
                    'program_uuid' => $program->uuid,
                    'api_url' => url('/api/v1/aether/programs/'.$program->uuid),
                ],
            ];
        }

        $mealDay = $program->nutritionDays->sortBy('day_index')->first();
        $mealCount = $mealDay?->meals->count() ?? 0;

        return [
            'status' => 'active',
            'lock' => null,
            'snippet' => [
                'type' => 'aether_meal',
                'headline' => __('missions.aether_meal_headline', [
                    'calories' => $program->metabolic_target_calories ?? $mealDay?->total_calories ?? 0,
                ], locale: $locale),
                'detail' => __('missions.aether_meal_detail', ['count' => $mealCount], locale: $locale),
                'metric' => [
                    'label' => __('missions.adherence_label', locale: $locale),
                    'value' => '0%',
                ],
            ],
            'insight' => filled($program->coach_habit_stack) ? [
                'source' => 'aether',
                'type' => 'coach_tip',
                'text' => (string) $program->coach_habit_stack,
                'priority' => 'normal',
                'expires_at' => null,
            ] : null,
            'deep_link' => [
                'type' => 'aether_program',
                'program_uuid' => $program->uuid,
                'api_url' => url('/api/v1/aether/programs/'.$program->uuid),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $capability
     * @param  array<string, mixed>  $fieldValues
     * @return array<string, mixed>
     */
    private function presentMissionToolSnippet(
        string $key,
        array $capability,
        array $fieldValues,
        MissionEnrollment $enrollment,
        string $locale,
    ): array {
        return match ($key) {
            'schedule' => $this->scheduleSnippet($fieldValues, $locale),
            'supplement' => $this->supplementSnippet($enrollment, $locale),
            'registration' => $this->registrationSnippet($capability, $fieldValues, $locale),
            'measurement' => [
                'type' => 'prompt',
                'headline' => __('missions.daily_check_in_headline', locale: $locale),
                'detail' => __('missions.daily_check_in_detail', locale: $locale),
                'metric' => null,
            ],
            'equipment' => [
                'type' => 'empty_state',
                'headline' => __('missions.equipment_empty_headline', locale: $locale),
                'detail' => __('missions.equipment_empty_detail', locale: $locale),
                'metric' => null,
            ],
            'mindset' => [
                'type' => 'prompt',
                'headline' => __('missions.mindset_prompt_headline', locale: $locale),
                'detail' => __('missions.mindset_prompt_detail', locale: $locale),
                'metric' => null,
            ],
            default => [
                'type' => 'empty_state',
                'headline' => $capability['label'],
                'detail' => __('missions.tool_ready', locale: $locale),
                'metric' => null,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $fieldValues
     * @return array<string, mixed>
     */
    private function scheduleSnippet(array $fieldValues, string $locale): array
    {
        $days = is_array($fieldValues['gym_days'] ?? null) ? $fieldValues['gym_days'] : [];
        $time = (string) ($fieldValues['preferred_gym_time'] ?? '');

        return [
            'type' => 'schedule_preview',
            'headline' => __('missions.schedule_days_headline', ['count' => count($days)], locale: $locale),
            'detail' => count($days) > 0
                ? implode(' · ', array_map(fn (string $day): string => Str::headline($day), $days))
                    .($time !== '' ? ' · '.$time : '')
                : __('missions.schedule_not_set', locale: $locale),
            'metric' => [
                'label' => __('missions.days_per_week_label', locale: $locale),
                'value' => (string) count($days),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function supplementSnippet(MissionEnrollment $enrollment, string $locale): array
    {
        $count = count($this->supplements->activeProducts($enrollment));

        if ($count === 0) {
            return [
                'type' => 'empty_state',
                'headline' => __('missions.supplements_empty_headline', locale: $locale),
                'detail' => __('missions.supplements_empty_detail', locale: $locale),
                'metric' => null,
            ];
        }

        return [
            'type' => 'metric',
            'headline' => __('missions.supplements_active_headline', locale: $locale),
            'detail' => __('missions.supplements_active_detail', ['count' => $count], locale: $locale),
            'metric' => [
                'label' => __('missions.products_label', locale: $locale),
                'value' => (string) $count,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $capability
     * @param  array<string, mixed>  $fieldValues
     * @return array<string, mixed>
     */
    private function registrationSnippet(array $capability, array $fieldValues, string $locale): array
    {
        $checklist = $capability['config']['checklist'] ?? [];
        $progress = is_array($fieldValues['registration_progress'] ?? null)
            ? $fieldValues['registration_progress']
            : [];
        $total = is_array($checklist) ? count($checklist) : 0;
        $completed = collect($progress)->filter(fn ($done): bool => (bool) $done)->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'type' => 'checklist_progress',
            'headline' => __('missions.registration_checklist_headline', locale: $locale),
            'detail' => __('missions.registration_checklist_detail', [
                'completed' => $completed,
                'total' => $total,
            ], locale: $locale),
            'metric' => [
                'label' => __('missions.complete_label', locale: $locale),
                'value' => $percent.'%',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAetherContext(
        MissionEnrollment $enrollment,
        User $user,
        string $locale,
        ?string $engineModule,
        ?AetherGeneratedProgram $workoutProgram,
        ?AetherGeneratedProgram $mealProgram,
        ?AetherUserProfile $profile,
    ): array {
        if ($engineModule !== 'aether') {
            return [];
        }

        $presenter = new MissionEnrollmentPresenter($enrollment);
        $capabilities = $presenter->enabledCapabilities($locale);
        $taskConfig = collect($capabilities)->firstWhere('key', 'task')['config'] ?? [];
        $nutritionConfig = collect($capabilities)->firstWhere('key', 'nutrition')['config'] ?? [];

        $userHasPro = MissionProGate::userHasProAccess($user);
        $canWorkout = MissionProGate::canUseFeature($user, $taskConfig, 'ai_workout_plan');
        $canMeal = MissionProGate::canUseFeature($user, $nutritionConfig, 'ai_meal_plan');
        $isCalibrated = $workoutProgram !== null || $mealProgram !== null;
        $profileComplete = $profile?->isQuestionnaireComplete() ?? false;

        $status = 'locked';
        $statusReason = 'calibration_required';

        if ($isCalibrated) {
            $status = 'active';
            $statusReason = null;
        } elseif (! $userHasPro && (! $canWorkout && ! $canMeal)) {
            $status = 'pro_required';
            $statusReason = 'pro_required';
        }

        $baseUrl = url('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration');

        return [
            'status' => $status,
            'status_reason' => $statusReason,
            'calibration' => [
                'is_required' => true,
                'is_complete' => $isCalibrated,
                'progress_percent' => $isCalibrated ? 100 : ($profileComplete ? 50 : 0),
                'current_step' => null,
                'total_steps' => self::CALIBRATION_TOTAL_STEPS,
                'can_resume' => $profileComplete && ! $isCalibrated,
                'last_saved_at' => $profile?->updated_at?->toIso8601String(),
                'completed_at' => $isCalibrated ? ($workoutProgram?->created_at ?? $mealProgram?->created_at)?->toIso8601String() : null,
                'defaults_api' => $baseUrl.'/defaults',
                'complete_api' => $baseUrl.'/complete',
                'regenerate_api' => $baseUrl.'/regenerate',
            ],
            'access' => [
                'can_calibrate' => $canWorkout || $canMeal,
                'can_generate_workout' => $canWorkout,
                'can_generate_meal' => $canMeal,
                'requires_pro_workout' => MissionProGate::featureRequiresPro($taskConfig, 'ai_workout_plan'),
                'requires_pro_meal' => MissionProGate::featureRequiresPro($nutritionConfig, 'ai_meal_plan'),
                'user_has_pro' => $userHasPro,
            ],
        ];
    }

    private function resolveWorkspaceMode(?string $engineModule, array $aetherContext): string
    {
        if ($engineModule !== 'aether') {
            return 'active';
        }

        return ($aetherContext['status'] ?? 'locked') === 'active' ? 'active' : 'locked';
    }

    private function isAetherPoweredTool(string $key): bool
    {
        return in_array($key, ['task', 'nutrition'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentAetherProfile(?AetherUserProfile $profile, string $locale): array
    {
        if ($profile === null) {
            return [
                'is_complete' => false,
                'uuid' => null,
                'summary' => null,
            ];
        }

        return [
            'is_complete' => $profile->isQuestionnaireComplete(),
            'uuid' => $profile->uuid,
            'summary' => $profile->isQuestionnaireComplete() ? [
                'primary_goal' => $profile->primary_goal->value,
                'primary_goal_label' => __('missions.ai_goal_'.$profile->primary_goal->value, locale: $locale),
                'training_days_per_week' => $profile->training_days_per_week,
                'session_duration' => $profile->session_duration->value,
                'equipment' => $profile->equipment->value,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLinkedProgram(AetherGeneratedProgram $program, string $locale, User $user): array
    {
        return [
            'uuid' => $program->uuid,
            'version' => $program->version,
            'applied_target' => $program->applied_target,
            'status' => $program->status->value,
            'split' => $program->split?->value,
            'summary' => $this->programHistory->summaryForProgram($program, $locale),
            'adherence_percent' => $program->applied_target === 'workout'
                ? $this->adherence->workoutAdherencePercent($user, $program)
                : null,
            'coach_title' => $program->coach_title,
            'week_focus' => $program->coach_week_focus,
            'api_url' => url('/api/v1/aether/programs/'.$program->uuid),
        ];
    }

    /**
     * @return list<array{slug: ?string, title: string, status: string}>
     */
    private function presentPhaseTimeline(MissionEnrollment $enrollment, string $locale): array
    {
        $snapshot = is_array($enrollment->template_snapshot) ? $enrollment->template_snapshot : [];
        $currentPhaseId = $enrollment->current_phase_id;
        $foundCurrent = false;

        return collect($snapshot['phases'] ?? [])
            ->sortBy('sort_order')
            ->values()
            ->map(function (array $phase) use ($locale, $currentPhaseId, &$foundCurrent): array {
                $isCurrent = ($phase['id'] ?? null) === $currentPhaseId;
                $status = 'upcoming';

                if ($isCurrent) {
                    $status = 'active';
                    $foundCurrent = true;
                } elseif (! $foundCurrent) {
                    $status = 'completed';
                }

                return [
                    'slug' => $phase['slug'] ?? null,
                    'title' => MissionLocalizedText::forLocale($phase['title'] ?? '', $locale),
                    'status' => $status,
                ];
            })
            ->all();
    }
}
