<?php

namespace Modules\MissionEngine\Services;

use Modules\MissionEngine\Models\MissionTemplate;

/**
 * Freezes a template definition so enrollments survive future admin edits.
 *
 * @phpstan-type MissionTemplateSnapshot array{
 *     template_id: int,
 *     template_uuid: string,
 *     slug: string,
 *     version: int,
 *     captured_at: string,
 *     title: array<string, string>,
 *     summary: array<string, string>|null,
 *     description: array<string, string>|null,
 *     difficulty: string,
 *     estimated_days: int|null,
 *     category: array{slug: string, name: array<string, string>}|null,
 *     capabilities: list<array<string, mixed>>,
 *     fields: list<array<string, mixed>>,
 *     phases: list<array<string, mixed>>,
 * }
 */
final class MissionTemplateSnapshotBuilder
{
    /**
     * @return MissionTemplateSnapshot
     */
    public function build(MissionTemplate $template): array
    {
        $template->loadMissing([
            'category',
            'capabilities.capabilityType',
            'fields.capabilityType',
            'phases',
        ]);

        return [
            'template_id' => $template->id,
            'template_uuid' => $template->uuid,
            'slug' => $template->slug,
            'version' => $template->version,
            'captured_at' => now()->toIso8601String(),
            'title' => $template->getTranslations('title'),
            'summary' => $template->getTranslations('summary'),
            'description' => $template->getTranslations('description'),
            'difficulty' => $template->difficulty?->value ?? 'beginner',
            'estimated_days' => $template->estimated_days,
            'category' => $template->category === null ? null : [
                'slug' => $template->category->slug,
                'name' => $template->category->getTranslations('name'),
            ],
            'capabilities' => $template->capabilities
                ->where('is_enabled', true)
                ->values()
                ->map(fn ($capability): array => [
                    'key' => $capability->capabilityType?->key?->value ?? 'unknown',
                    'label' => $capability->getTranslations('label'),
                    'config' => $capability->config ?? [],
                    'sort_order' => $capability->sort_order,
                ])
                ->all(),
            'fields' => $template->fields
                ->values()
                ->map(fn ($field): array => [
                    'field_key' => $field->field_key,
                    'capability_key' => $field->capabilityType?->key->value,
                    'label' => $field->getTranslations('label'),
                    'help_text' => $field->getTranslations('help_text'),
                    'field_type' => $field->field_type->value,
                    'options' => $field->options,
                    'validation_rules' => $field->validation_rules,
                    'default_value' => $field->default_value,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                    'section' => $field->section,
                    'conditional_logic' => $field->conditional_logic,
                ])
                ->all(),
            'phases' => $template->phases
                ->values()
                ->map(fn ($phase): array => [
                    'id' => $phase->id,
                    'slug' => $phase->slug,
                    'title' => $phase->getTranslations('title'),
                    'description' => $phase->getTranslations('description'),
                    'sort_order' => $phase->sort_order,
                    'duration_days' => $phase->duration_days,
                    'required_completion_count' => $phase->required_completion_count,
                    'meta' => $phase->meta,
                ])
                ->all(),
        ];
    }
}
