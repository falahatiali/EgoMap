<?php

namespace Modules\MissionEngine\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\MissionEngine\Enums\MissionCapabilityKey;
use Modules\MissionEngine\Enums\MissionFieldType;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Models\MissionTemplateCapability;
use Modules\MissionEngine\Models\MissionTemplateField;
use Modules\MissionEngine\Models\MissionTemplatePhase;

final class MissionTemplateAdminService
{
    public function __construct(
        private readonly MissionTemplateCapabilitySync $capabilitySync,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createField(MissionTemplate $template, array $data): MissionTemplateField
    {
        $fieldKey = Str::slug((string) ($data['field_key'] ?? ''), '_');

        if ($fieldKey === '') {
            $fieldKey = 'field_'.Str::random(6);
        }

        $sortOrder = (int) ($template->fields()->max('sort_order') ?? 0) + 10;

        $field = $template->fields()->make([
            'field_key' => $fieldKey,
            'capability_type_id' => $data['capability_type_id'] ?? null,
            'field_type' => MissionFieldType::from((string) ($data['field_type'] ?? MissionFieldType::Text->value)),
            'section' => filled($data['section'] ?? null) ? (string) $data['section'] : null,
            'options' => $this->decodeJsonColumn($data['options_json'] ?? null, 'options_json'),
            'default_value' => $this->decodeJsonColumn($data['default_value_json'] ?? null, 'default_value_json', allowScalar: true),
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => $sortOrder,
        ]);

        $field->setTranslation('label', 'en', (string) ($data['label_en'] ?? $fieldKey));
        $field->setTranslation('label', 'fa', (string) ($data['label_fa'] ?? ''));
        $field->setTranslation('help_text', 'en', (string) ($data['help_en'] ?? ''));
        $field->setTranslation('help_text', 'fa', (string) ($data['help_fa'] ?? ''));
        $field->save();

        return $field;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateField(MissionTemplateField $field, array $data): MissionTemplateField
    {
        $fieldKey = Str::slug((string) ($data['field_key'] ?? $field->field_key), '_');

        if ($fieldKey === '') {
            throw ValidationException::withMessages([
                'field_key' => [__('admin.mission_engine.field_key_required')],
            ]);
        }

        $field->update([
            'field_key' => $fieldKey,
            'capability_type_id' => $data['capability_type_id'] ?? null,
            'field_type' => MissionFieldType::from((string) $data['field_type']),
            'section' => filled($data['section'] ?? null) ? (string) $data['section'] : null,
            'options' => $this->decodeJsonColumn($data['options_json'] ?? null, 'options_json'),
            'default_value' => $this->decodeJsonColumn($data['default_value_json'] ?? null, 'default_value_json', allowScalar: true),
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? $field->sort_order),
        ]);

        $field->setTranslation('label', 'en', (string) ($data['label_en'] ?? ''));
        $field->setTranslation('label', 'fa', (string) ($data['label_fa'] ?? ''));
        $field->setTranslation('help_text', 'en', (string) ($data['help_en'] ?? ''));
        $field->setTranslation('help_text', 'fa', (string) ($data['help_fa'] ?? ''));
        $field->save();

        return $field->refresh();
    }

    public function deleteField(MissionTemplateField $field): void
    {
        $field->delete();
    }

    public function moveField(MissionTemplateField $field, string $direction): void
    {
        $siblingQuery = $field->template->fields()->orderBy('sort_order');

        if ($direction === 'up') {
            $swap = (clone $siblingQuery)
                ->where('sort_order', '<', $field->sort_order)
                ->orderByDesc('sort_order')
                ->first();
        } else {
            $swap = (clone $siblingQuery)
                ->where('sort_order', '>', $field->sort_order)
                ->orderBy('sort_order')
                ->first();
        }

        if ($swap === null) {
            return;
        }

        $currentOrder = $field->sort_order;
        $field->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $currentOrder]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPhase(MissionTemplate $template, array $data): MissionTemplatePhase
    {
        $slug = Str::slug((string) ($data['slug'] ?? ''), '_');

        if ($slug === '') {
            $slug = 'phase_'.Str::random(6);
        }

        $sortOrder = (int) ($template->phases()->max('sort_order') ?? 0) + 10;

        $phase = $template->phases()->make([
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'duration_days' => filled($data['duration_days'] ?? null) ? (int) $data['duration_days'] : null,
            'required_completion_count' => filled($data['required_completion_count'] ?? null)
                ? (int) $data['required_completion_count']
                : null,
        ]);

        $phase->setTranslation('title', 'en', (string) ($data['title_en'] ?? $slug));
        $phase->setTranslation('title', 'fa', (string) ($data['title_fa'] ?? ''));
        $phase->setTranslation('description', 'en', (string) ($data['description_en'] ?? ''));
        $phase->setTranslation('description', 'fa', (string) ($data['description_fa'] ?? ''));
        $phase->save();

        return $phase;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePhase(MissionTemplatePhase $phase, array $data): MissionTemplatePhase
    {
        $slug = Str::slug((string) ($data['slug'] ?? $phase->slug), '_');

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => [__('admin.mission_engine.phase_slug_required')],
            ]);
        }

        $phase->update([
            'slug' => $slug,
            'sort_order' => (int) ($data['sort_order'] ?? $phase->sort_order),
            'duration_days' => filled($data['duration_days'] ?? null) ? (int) $data['duration_days'] : null,
            'required_completion_count' => filled($data['required_completion_count'] ?? null)
                ? (int) $data['required_completion_count']
                : null,
        ]);

        $phase->setTranslation('title', 'en', (string) ($data['title_en'] ?? ''));
        $phase->setTranslation('title', 'fa', (string) ($data['title_fa'] ?? ''));
        $phase->setTranslation('description', 'en', (string) ($data['description_en'] ?? ''));
        $phase->setTranslation('description', 'fa', (string) ($data['description_fa'] ?? ''));
        $phase->save();

        return $phase->refresh();
    }

    public function deletePhase(MissionTemplatePhase $phase): void
    {
        $phase->delete();
    }

    public function movePhase(MissionTemplatePhase $phase, string $direction): void
    {
        $siblingQuery = $phase->template->phases()->orderBy('sort_order');

        if ($direction === 'up') {
            $swap = (clone $siblingQuery)
                ->where('sort_order', '<', $phase->sort_order)
                ->orderByDesc('sort_order')
                ->first();
        } else {
            $swap = (clone $siblingQuery)
                ->where('sort_order', '>', $phase->sort_order)
                ->orderBy('sort_order')
                ->first();
        }

        if ($swap === null) {
            return;
        }

        $currentOrder = $phase->sort_order;
        $phase->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $currentOrder]);
    }

    /**
     * @param  list<int>  $enabledCapabilityIds
     * @param  array<int, array<string, mixed>>  $configsByCapabilityTypeId
     */
    public function syncCapabilities(
        MissionTemplate $template,
        array $enabledCapabilityIds,
        array $configsByCapabilityTypeId = [],
    ): void {
        $this->capabilitySync->sync($template, $enabledCapabilityIds);

        foreach ($configsByCapabilityTypeId as $capabilityTypeId => $config) {
            if ($config === []) {
                continue;
            }

            MissionTemplateCapability::query()
                ->where('template_id', $template->id)
                ->where('capability_type_id', (int) $capabilityTypeId)
                ->update(['config' => $config]);
        }
    }

    /**
     * @return array{ok: bool, warnings: list<string>}
     */
    public function publishReadiness(MissionTemplate $template): array
    {
        $warnings = [];

        if ($template->fields()->count() === 0) {
            $warnings[] = __('admin.mission_engine.warn_no_fields');
        }

        if ($template->phases()->count() === 0) {
            $warnings[] = __('admin.mission_engine.warn_no_phases');
        }

        if ($template->capabilities()->where('is_enabled', true)->count() === 0) {
            $warnings[] = __('admin.mission_engine.warn_no_capabilities');
        }

        $title = $template->getTranslation('title', 'en', true);

        if (! is_string($title) || trim($title) === '') {
            $warnings[] = __('admin.mission_engine.warn_no_title');
        }

        return [
            'ok' => $warnings === [],
            'warnings' => $warnings,
        ];
    }

    public function duplicate(MissionTemplate $template, ?int $createdBy = null): MissionTemplate
    {
        return DB::transaction(function () use ($template, $createdBy): MissionTemplate {
            $copy = $template->replicate([
                'uuid',
                'published_at',
            ]);
            $copy->slug = $this->uniqueSlug($template->slug.'-copy');
            $copy->status = MissionTemplateStatus::Draft;
            $copy->published_at = null;
            $copy->version = 1;
            $copy->is_featured = false;
            $copy->created_by = $createdBy;
            $copy->save();

            foreach ($template->capabilities as $capability) {
                $copy->capabilities()->create([
                    'capability_type_id' => $capability->capability_type_id,
                    'is_enabled' => $capability->is_enabled,
                    'config' => $capability->config,
                    'sort_order' => $capability->sort_order,
                ]);
            }

            foreach ($template->fields as $field) {
                $newField = $field->replicate();
                $newField->template_id = $copy->id;
                $newField->save();
            }

            foreach ($template->phases as $phase) {
                $newPhase = $phase->replicate();
                $newPhase->template_id = $copy->id;
                $newPhase->save();
            }

            return $copy->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfigForCapability(MissionCapabilityKey $key): array
    {
        return match ($key) {
            MissionCapabilityKey::Registration => [
                'checklist' => [
                    ['key' => 'step_1', 'label' => ['en' => 'First step', 'fa' => '']],
                ],
            ],
            MissionCapabilityKey::Measurement => [
                'metrics' => [
                    ['key' => 'weight', 'unit' => 'kg', 'label' => ['en' => 'Body weight', 'fa' => '']],
                ],
            ],
            MissionCapabilityKey::Task => [
                'features' => [
                    'ai_workout_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI workout plan', 'fa' => ''],
                    ],
                ],
            ],
            MissionCapabilityKey::Nutrition => [
                'features' => [
                    'ai_meal_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI meal plan', 'fa' => ''],
                    ],
                ],
            ],
            default => [],
        };
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $suffix = 2;

        while (MissionTemplate::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function decodeJsonColumn(mixed $raw, string $attribute, bool $allowScalar = false): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                $attribute => [__('admin.mission_engine.invalid_json')],
            ]);
        }

        if ($allowScalar && ! is_array($decoded)) {
            return $decoded;
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $attribute => [__('admin.mission_engine.invalid_json_object')],
            ]);
        }

        return $decoded;
    }
}
