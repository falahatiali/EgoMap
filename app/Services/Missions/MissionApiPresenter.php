<?php

namespace App\Services\Missions;

use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Support\MissionEnrollmentPresenter;

final class MissionApiPresenter
{
    /**
     * @return array<string, string>
     */
    public function labels(string $locale): array
    {
        return [
            'page_title' => __('missions.page_title', locale: $locale),
            'catalog_title' => __('missions.catalog_title', locale: $locale),
            'catalog_subtitle' => __('missions.catalog_subtitle', locale: $locale),
            'catalog_empty' => __('missions.catalog_empty', locale: $locale),
            'featured' => __('missions.featured', locale: $locale),
            'start_mission' => __('missions.start_mission', locale: $locale),
            'continue_mission' => __('missions.continue_mission', locale: $locale),
            'already_active' => __('missions.already_active', locale: $locale),
            'includes' => __('missions.includes', locale: $locale),
            'phases' => __('missions.phases', locale: $locale),
            'workspace_title' => __('missions.workspace_title', locale: $locale),
            'workspace_subtitle' => __('missions.workspace_subtitle', locale: $locale),
            'my_missions_title' => __('missions.my_missions_title', locale: $locale),
            'my_missions_subtitle' => __('missions.my_missions_subtitle', locale: $locale),
            'browse_missions' => __('missions.browse_missions', locale: $locale),
            'no_active_missions' => __('missions.no_active_missions', locale: $locale),
            'days' => __('missions.days', ['count' => ':count'], locale: $locale),
            'ghost_hint' => __('missions.ghost_hint', locale: $locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentTemplate(MissionTemplate $template, string $locale, ?MissionEnrollment $enrollment = null): array
    {
        $capabilities = $template->capabilities
            ->where('is_enabled', true)
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($cap): array => [
                'key' => $cap->capabilityType->key->value ?? '',
                'name' => $cap->capabilityType->getTranslation('name', $locale, true)
                    ?: $cap->capabilityType->getTranslation('name', 'en', true),
                'icon' => $cap->capabilityType->icon ?? 'fa-circle',
            ])
            ->all();

        $phases = $template->phases
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($phase): array => [
                'slug' => $phase->slug,
                'title' => $phase->getTranslation('title', $locale, true)
                    ?: $phase->getTranslation('title', 'en', true),
                'duration_days' => $phase->duration_days,
            ])
            ->all();

        return array_merge(
            $this->presentTemplateCard($template, $locale, $enrollment),
            [
                'description' => $template->getTranslation('description', $locale, true)
                    ?: $template->getTranslation('description', 'en', true),
                'phases' => $phases,
                'capabilities' => $capabilities,
                'enrollment' => $enrollment !== null
                    ? $this->presentEnrollmentSummary($enrollment, $locale)
                    : null,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function presentTemplateCard(MissionTemplate $template, string $locale, ?MissionEnrollment $enrollment = null): array
    {
        $categoryName = $template->category?->getTranslation('name', $locale, true)
            ?: $template->category?->getTranslation('name', 'en', true);

        return [
            'slug' => $template->slug,
            'title' => $template->getTranslation('title', $locale, true)
                ?: $template->getTranslation('title', 'en', true),
            'summary' => $template->getTranslation('summary', $locale, true)
                ?: $template->getTranslation('summary', 'en', true),
            'icon' => $template->icon ?? 'fa-flag',
            'category' => $categoryName,
            'estimated_days' => $template->estimated_days,
            'is_featured' => (bool) $template->is_featured,
            'ghost_mode_recommended' => (bool) ($template->meta['ghost_mode_recommended'] ?? false),
            'accent' => (string) ($template->meta['accent'] ?? 'emerald'),
            'has_active_enrollment' => $enrollment !== null && $enrollment->status->value === 'active',
            'enrollment_uuid' => $enrollment?->uuid,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentEnrollmentSummary(MissionEnrollment $enrollment, string $locale): array
    {
        $presenter = new MissionEnrollmentPresenter($enrollment);
        $snapshot = $enrollment->template_snapshot ?? [];
        $currentPhaseTitle = null;

        foreach ($snapshot['phases'] ?? [] as $phase) {
            if (($phase['id'] ?? null) === $enrollment->current_phase_id) {
                $currentPhaseTitle = $phase['title'][$locale]
                    ?? $phase['title']['en']
                    ?? null;
                break;
            }
        }

        return [
            'uuid' => $enrollment->uuid,
            'status' => $enrollment->status->value,
            'title' => $presenter->title($locale),
            'template_slug' => $snapshot['slug'] ?? $enrollment->template?->slug,
            'icon' => $snapshot['icon'] ?? 'fa-flag',
            'progress_percent' => (float) $enrollment->progress_percent,
            'started_at' => $enrollment->started_at?->toIso8601String(),
            'last_activity_at' => $enrollment->last_activity_at?->toIso8601String(),
            'current_phase_title' => is_string($currentPhaseTitle) ? $currentPhaseTitle : null,
            'estimated_days' => $snapshot['estimated_days'] ?? null,
            'capabilities' => $presenter->enabledCapabilities($locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentWorkspace(MissionEnrollment $enrollment, string $locale): array
    {
        $presenter = new MissionEnrollmentPresenter($enrollment);

        return [
            'enrollment' => $this->presentEnrollmentSummary($enrollment, $locale),
            'tabs' => [
                ['key' => 'workout', 'label' => __('missions.tab_workout', locale: $locale), 'icon' => 'fa-dumbbell'],
                ['key' => 'nutrition', 'label' => __('missions.tab_nutrition', locale: $locale), 'icon' => 'fa-utensils'],
                ['key' => 'supplements', 'label' => __('missions.tab_supplements', locale: $locale), 'icon' => 'fa-capsules'],
                ['key' => 'daily', 'label' => __('missions.tab_daily', locale: $locale), 'icon' => 'fa-calendar-day'],
                ['key' => 'schedule', 'label' => __('missions.tab_schedule', locale: $locale), 'icon' => 'fa-calendar-week'],
                ['key' => 'equipment', 'label' => __('missions.tab_equipment', locale: $locale), 'icon' => 'fa-bag-shopping'],
                ['key' => 'registration', 'label' => __('missions.tab_registration', locale: $locale), 'icon' => 'fa-clipboard-check'],
            ],
            'copy' => [
                'workspace_title' => __('missions.workspace_title', locale: $locale),
                'workspace_subtitle' => __('missions.workspace_subtitle', locale: $locale),
                'mobile_coming_soon' => __('missions.mobile_workspace_coming_soon', locale: $locale),
            ],
            'field_values' => $presenter->fieldValues(),
        ];
    }
}
