<?php

namespace Modules\MissionEngine\Services;

use App\Models\User;
use App\Services\Missions\MissionAetherAdherenceService;
use App\Services\Missions\MissionAetherProgramService;
use App\Services\Profile\UserAetherProgramHistoryService;
use App\Support\LocaleConfig;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionDailyReport;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Models\MissionTemplateField;
use Modules\MissionEngine\Support\MissionEnrollmentPresenter;
use Modules\MissionEngine\Support\MissionLocalizedText;
use Modules\MissionEngine\Support\MissionProGate;

final class MissionApiService
{
    public function __construct(
        private MissionAetherAdherenceService $adherence,
        private MissionAetherProgramService $aetherPrograms,
        private UserAetherProgramHistoryService $programHistory,
        private MissionDailyReportService $dailyReports,
        private MissionSupplementLogService $supplements,
    ) {}

    public function resolveLocale(?string $acceptLanguage = null): string
    {
        return LocaleConfig::resolve($acceptLanguage ?? app()->getLocale());
    }

    /**
     * @return array{missions: list<array<string, mixed>>, labels: array<string, string>}
     */
    public function catalog(?User $user, string $locale): array
    {
        $templates = MissionTemplate::query()
            ->with('category')
            ->where('status', MissionTemplateStatus::Published)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get();

        $enrollmentsByTemplate = collect();

        if ($user !== null) {
            $enrollmentsByTemplate = MissionEnrollment::query()
                ->where('user_id', $user->id)
                ->whereIn('template_id', $templates->pluck('id'))
                ->where('status', MissionEnrollmentStatus::Active)
                ->get()
                ->keyBy('template_id');
        }

        return [
            'missions' => $templates
                ->map(fn (MissionTemplate $template): array => $this->mapCatalogTemplate(
                    $template,
                    $locale,
                    $enrollmentsByTemplate->get($template->id),
                ))
                ->values()
                ->all(),
            'labels' => $this->catalogLabels($locale),
        ];
    }

    /**
     * @return array{mission: array<string, mixed>}
     */
    public function templateDetail(MissionTemplate $template, ?User $user, string $locale): array
    {
        abort_unless($template->isPublished(), 404);

        $enrollment = null;

        if ($user !== null) {
            $enrollment = MissionEnrollment::query()
                ->where('user_id', $user->id)
                ->where('template_id', $template->id)
                ->where('status', MissionEnrollmentStatus::Active)
                ->first();
        }

        $template->loadMissing(['category', 'phases', 'capabilities.capabilityType', 'fields']);

        return [
            'mission' => array_merge(
                $this->mapCatalogTemplate($template, $locale, $enrollment),
                [
                    'description' => $template->getTranslation('description', $locale, true),
                    'phases' => $template->phases
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($phase): array => [
                            'slug' => $phase->slug,
                            'title' => $phase->getTranslation('title', $locale, true),
                            'description' => $phase->getTranslation('description', $locale, true),
                            'duration_days' => $phase->duration_days,
                            'sort_order' => $phase->sort_order,
                        ])
                        ->all(),
                    'capabilities' => $template->capabilities
                        ->where('is_enabled', true)
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($capability): array => [
                            'key' => $capability->capabilityType?->key?->value,
                            'label' => $capability->getTranslation('label', $locale, true)
                                ?: $capability->capabilityType?->getTranslation('name', $locale, true),
                            'config' => $capability->config ?? [],
                        ])
                        ->all(),
                    'fields' => $template->fields
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($field): array => $this->mapFieldDefinition($field, $locale))
                        ->all(),
                    'active_enrollment_uuid' => $enrollment?->uuid,
                ],
            ),
        ];
    }

    /**
     * @return array{enrollments: list<array<string, mixed>>}
     */
    public function userEnrollments(User $user, string $locale): array
    {
        $enrollments = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', MissionEnrollmentStatus::Active)
            ->latest('last_activity_at')
            ->get();

        return [
            'enrollments' => $enrollments
                ->map(fn (MissionEnrollment $enrollment): array => $this->mapEnrollmentSummary($enrollment, $locale))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{enrollment: array<string, mixed>}
     */
    public function enrollmentWorkspace(MissionEnrollment $enrollment, User $user, string $locale): array
    {
        $this->assertOwned($enrollment, $user);

        $enrollment->loadMissing(['measurements' => fn ($q) => $q->latest('measured_at')->limit(10)]);
        $presenter = new MissionEnrollmentPresenter($enrollment);
        $capabilities = $presenter->enabledCapabilities($locale);

        $taskConfig = collect($capabilities)->firstWhere('key', 'task')['config'] ?? [];
        $nutritionConfig = collect($capabilities)->firstWhere('key', 'nutrition')['config'] ?? [];

        $workoutProgram = $this->adherence->latestProgramForEnrollment($enrollment, 'workout');
        $mealProgram = $this->adherence->latestProgramForEnrollment($enrollment, 'meal');

        return [
            'enrollment' => array_merge(
                $this->mapEnrollmentSummary($enrollment, $locale),
                [
                    'field_values' => $enrollment->field_values ?? [],
                    'capabilities' => $capabilities,
                    'tabs' => $this->workspaceTabs($locale),
                    'programs' => [
                        'workout' => $workoutProgram ? $this->mapLinkedProgram($workoutProgram, $locale, $user) : null,
                        'meal' => $mealProgram ? $this->mapLinkedProgram($mealProgram, $locale, $user) : null,
                    ],
                    'ai' => [
                        'can_workout' => MissionProGate::canUseFeature($user, $taskConfig, 'ai_workout_plan'),
                        'can_meal' => MissionProGate::canUseFeature($user, $nutritionConfig, 'ai_meal_plan'),
                        'requires_pro_workout' => MissionProGate::featureRequiresPro($taskConfig, 'ai_workout_plan'),
                        'requires_pro_meal' => MissionProGate::featureRequiresPro($nutritionConfig, 'ai_meal_plan'),
                    ],
                    'supplements' => [
                        'products' => collect($this->supplements->activeProducts($enrollment))
                            ->map(fn ($product): array => [
                                'id' => $product->id,
                                'uuid' => $product->uuid,
                                'name' => $product->name,
                                'brand' => $product->brand,
                                'default_unit' => $product->default_unit,
                                'default_amount' => $product->default_amount,
                            ])
                            ->all(),
                    ],
                    'recent_measurements' => $enrollment->measurements
                        ->map(fn ($measurement): array => [
                            'metric_key' => $measurement->metric_key,
                            'value' => (float) $measurement->value,
                            'unit' => $measurement->unit,
                            'measured_at' => $measurement->measured_at?->toIso8601String(),
                        ])
                        ->all(),
                ],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $wizard
     * @return array{program: array<string, mixed>}
     */
    public function generateProgram(
        MissionEnrollment $enrollment,
        User $user,
        string $appliedTarget,
        array $wizard,
        string $locale,
    ): array {
        $this->assertOwned($enrollment, $user);

        $presenter = new MissionEnrollmentPresenter($enrollment);
        $capabilities = $presenter->enabledCapabilities($locale);
        $taskConfig = collect($capabilities)->firstWhere('key', 'task')['config'] ?? [];
        $nutritionConfig = collect($capabilities)->firstWhere('key', 'nutrition')['config'] ?? [];

        $allowed = match ($appliedTarget) {
            'workout' => MissionProGate::canUseFeature($user, $taskConfig, 'ai_workout_plan'),
            'meal' => MissionProGate::canUseFeature($user, $nutritionConfig, 'ai_meal_plan'),
            default => false,
        };

        abort_unless($allowed, 403, __('missions.pro_hint'));

        $program = $this->aetherPrograms->generate($user, $wizard, $enrollment, $appliedTarget);

        return [
            'program' => $this->mapLinkedProgram($program->fresh(), $locale, $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dailyReportForDate(MissionEnrollment $enrollment, User $user, string $date): array
    {
        $this->assertOwned($enrollment, $user);

        $report = $this->dailyReports->findForDate($enrollment, $date);

        if ($report === null) {
            return [
                'report_date' => $date,
                'body_weight' => null,
                'mood_score' => null,
                'energy_score' => null,
                'sleep_hours' => null,
                'trained_today' => $this->adherence->trainedOnDate($user, $date),
                'nutrition_logged' => false,
                'highlights' => null,
                'challenges' => null,
                'notes' => null,
            ];
        }

        return $this->mapDailyReport($report);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveDailyReport(MissionEnrollment $enrollment, User $user, array $payload): array
    {
        $this->assertOwned($enrollment, $user);

        $date = (string) $payload['report_date'];
        $trained = (bool) ($payload['trained_today'] ?? false)
            || $this->adherence->trainedOnDate($user, $date);

        $report = $this->dailyReports->save($enrollment, $user, [
            'report_date' => $date,
            'body_weight' => $payload['body_weight'] ?? null,
            'mood_score' => $payload['mood_score'] ?? null,
            'energy_score' => $payload['energy_score'] ?? null,
            'sleep_hours' => $payload['sleep_hours'] ?? null,
            'trained_today' => $trained,
            'nutrition_logged' => (bool) ($payload['nutrition_logged'] ?? false),
            'highlights' => $payload['highlights'] ?? null,
            'challenges' => $payload['challenges'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->adherence->syncEnrollmentProgress($enrollment->fresh(), $user);

        return $this->mapDailyReport($report);
    }

    /**
     * @return array<string, string>
     */
    private function catalogLabels(string $locale): array
    {
        return [
            'page_title' => __('missions.catalog_title', locale: $locale),
            'page_subtitle' => __('missions.catalog_subtitle', locale: $locale),
            'featured' => __('missions.featured', locale: $locale),
            'start_mission' => __('missions.start_mission', locale: $locale),
            'continue_mission' => __('missions.continue_mission', locale: $locale),
            'days' => __('missions.days', locale: $locale),
        ];
    }

    /**
     * @return list<array{key: string, label: string, icon: string}>
     */
    private function workspaceTabs(string $locale): array
    {
        return [
            ['key' => 'program', 'label' => __('missions.tab_program', locale: $locale), 'icon' => 'fa-bolt'],
            ['key' => 'supplements', 'label' => __('missions.tab_supplements', locale: $locale), 'icon' => 'fa-capsules'],
            ['key' => 'daily', 'label' => __('missions.tab_daily', locale: $locale), 'icon' => 'fa-calendar-day'],
            ['key' => 'schedule', 'label' => __('missions.tab_schedule', locale: $locale), 'icon' => 'fa-calendar-week'],
            ['key' => 'equipment', 'label' => __('missions.tab_equipment', locale: $locale), 'icon' => 'fa-bag-shopping'],
            ['key' => 'registration', 'label' => __('missions.tab_registration', locale: $locale), 'icon' => 'fa-clipboard-check'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCatalogTemplate(
        MissionTemplate $template,
        string $locale,
        ?MissionEnrollment $enrollment,
    ): array {
        $meta = is_array($template->meta) ? $template->meta : [];
        $highlights = $meta['highlights'][$locale] ?? $meta['highlights']['en'] ?? [];

        return [
            'slug' => $template->slug,
            'uuid' => $template->uuid,
            'title' => $template->getTranslation('title', $locale, true),
            'summary' => $template->getTranslation('summary', $locale, true),
            'icon' => $template->icon,
            'difficulty' => $template->difficulty?->value,
            'estimated_days' => $template->estimated_days,
            'is_featured' => $template->is_featured,
            'category' => $template->category === null ? null : [
                'slug' => $template->category->slug,
                'name' => $template->category->getTranslation('name', $locale, true),
                'icon' => $template->category->icon,
            ],
            'meta' => [
                'accent' => $meta['accent'] ?? null,
                'ghost_mode_recommended' => (bool) ($meta['ghost_mode_recommended'] ?? false),
                'engine_module' => $meta['engine_module'] ?? null,
                'highlights' => is_array($highlights) ? array_values($highlights) : [],
                'outcomes' => $meta['outcomes'][$locale] ?? $meta['outcomes']['en'] ?? [],
            ],
            'has_active_enrollment' => $enrollment !== null,
            'active_enrollment_uuid' => $enrollment?->uuid,
            'progress_percent' => $enrollment !== null ? (float) $enrollment->progress_percent : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEnrollmentSummary(MissionEnrollment $enrollment, string $locale): array
    {
        $presenter = new MissionEnrollmentPresenter($enrollment);
        $snapshot = is_array($enrollment->template_snapshot) ? $enrollment->template_snapshot : [];

        return [
            'uuid' => $enrollment->uuid,
            'title' => $presenter->title($locale),
            'status' => $enrollment->status->value,
            'progress_percent' => (float) $enrollment->progress_percent,
            'started_at' => $enrollment->started_at?->toIso8601String(),
            'last_activity_at' => $enrollment->last_activity_at?->toIso8601String(),
            'template' => [
                'slug' => $snapshot['slug'] ?? null,
                'icon' => $snapshot['icon'] ?? 'fa-compass',
                'estimated_days' => $snapshot['estimated_days'] ?? null,
            ],
            'current_phase' => collect($snapshot['phases'] ?? [])
                ->firstWhere('id', $enrollment->current_phase_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLinkedProgram(
        AetherGeneratedProgram $program,
        string $locale,
        User $user,
    ): array {
        return [
            'uuid' => $program->uuid,
            'version' => $program->version,
            'applied_target' => $program->applied_target,
            'status' => $program->status->value,
            'summary' => $this->programHistory->summaryForProgram($program, $locale),
            'adherence_percent' => $program->applied_target === 'workout'
                ? $this->adherence->workoutAdherencePercent($user, $program)
                : null,
            'api_url' => url('/api/v1/aether/programs/'.$program->uuid),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDailyReport(MissionDailyReport $report): array
    {
        return [
            'uuid' => $report->uuid,
            'report_date' => $report->report_date->toDateString(),
            'body_weight' => $report->body_weight !== null ? (float) $report->body_weight : null,
            'mood_score' => $report->mood_score,
            'energy_score' => $report->energy_score,
            'sleep_hours' => $report->sleep_hours !== null ? (float) $report->sleep_hours : null,
            'trained_today' => $report->trained_today,
            'nutrition_logged' => $report->nutrition_logged,
            'highlights' => $report->highlights,
            'challenges' => $report->challenges,
            'notes' => $report->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapFieldDefinition(MissionTemplateField $field, string $locale): array
    {
        $options = $field->options;

        if (is_array($options)) {
            $options = collect($options)
                ->map(fn ($option): array => [
                    'value' => $option['value'] ?? null,
                    'label' => MissionLocalizedText::forLocale($option['label'] ?? '', $locale),
                ])
                ->all();
        }

        return [
            'field_key' => $field->field_key,
            'field_type' => $field->field_type->value,
            'section' => $field->section,
            'label' => $field->getTranslation('label', $locale, true),
            'help_text' => $field->getTranslation('help_text', $locale, true),
            'options' => $options,
            'default_value' => $field->default_value,
            'is_required' => $field->is_required,
        ];
    }

    private function assertOwned(MissionEnrollment $enrollment, User $user): void
    {
        abort_unless($enrollment->user_id === $user->id, 403);
    }
}
