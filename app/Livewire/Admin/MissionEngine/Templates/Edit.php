<?php

namespace App\Livewire\Admin\MissionEngine\Templates;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Support\LocaleConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\MissionEngine\Enums\MissionCapabilityKey;
use Modules\MissionEngine\Enums\MissionDifficulty;
use Modules\MissionEngine\Enums\MissionFieldType;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionCapabilityType;
use Modules\MissionEngine\Models\MissionCategory;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Models\MissionTemplateField;
use Modules\MissionEngine\Models\MissionTemplatePhase;
use Modules\MissionEngine\Services\MissionTemplateAdminService;
use Modules\MissionEngine\Support\MissionCapabilityConfigExamples;

class Edit extends Component
{
    use WithAdminPage;
    use WithPagination;

    public MissionTemplate $template;

    public string $activeTab = 'details';

    public string $slug = '';

    public string $titleEn = '';

    public string $titleFa = '';

    public string $summaryEn = '';

    public string $summaryFa = '';

    public string $descriptionEn = '';

    public string $descriptionFa = '';

    public ?int $categoryId = null;

    public string $difficulty = '';

    public ?int $estimatedDays = null;

    public string $status = '';

    public bool $isFeatured = false;

    public string $icon = '';

    public int $sortOrder = 0;

    public bool $ghostModeRecommended = false;

    public string $accent = 'emerald';

    /** @var list<int> */
    public array $enabledCapabilityIds = [];

    /** @var array<int, string> */
    public array $capabilityConfigJson = [];

    /** @var list<array<string, mixed>> */
    public array $fieldDrafts = [];

    /** @var list<array<string, mixed>> */
    public array $phaseDrafts = [];

    public ?string $pageNotice = null;

    public string $pageNoticeType = 'success';

    /** @var list<string> */
    public array $lastSaveErrors = [];

    public function mount(MissionTemplate $template): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);

        $this->template = $template->load(['capabilities.capabilityType', 'category']);
        $this->hydrateDetailsFromTemplate();
        $this->hydrateCapabilityState();
        $this->refreshFieldDrafts();
        $this->refreshPhaseDrafts();

        if (request()->has('tab') && is_string(request('tab'))) {
            $this->activeTab = request('tab');
        }
    }

    public function setTab(string $tab): void
    {
        $allowed = ['details', 'capabilities', 'fields', 'phases', 'enrollments'];

        if (in_array($tab, $allowed, true)) {
            $this->activeTab = $tab;
        }
    }

    public function saveDetails(): void
    {
        $this->authorizeMissionAdmin();
        $this->normalizeDetailsForm();
        $this->slug = Str::slug($this->slug);
        $this->lastSaveErrors = [];

        $validated = $this->validate($this->detailsRules());

        $status = MissionTemplateStatus::from($validated['status']);
        $publishedAt = $this->template->published_at;

        if ($status === MissionTemplateStatus::Published && $publishedAt === null) {
            $readiness = app(MissionTemplateAdminService::class)->publishReadiness($this->template->fresh());

            if (! $readiness['ok']) {
                $this->lastSaveErrors = $readiness['warnings'];
                $this->status = $this->template->status->value;
                $this->adminFlash(__('admin.mission_engine.publish_blocked'), 'danger');

                return;
            }

            $publishedAt = now();
        }

        if ($status !== MissionTemplateStatus::Published) {
            $publishedAt = null;
        }

        $this->template->update([
            'slug' => $validated['slug'],
            'category_id' => $validated['categoryId'],
            'difficulty' => $validated['difficulty'],
            'estimated_days' => $validated['estimatedDays'],
            'status' => $status,
            'is_featured' => $validated['isFeatured'],
            'published_at' => $publishedAt,
            'icon' => $validated['icon'] ?: null,
            'sort_order' => $validated['sortOrder'],
            'meta' => [
                'ghost_mode_recommended' => $validated['ghostModeRecommended'],
                'accent' => $validated['accent'],
            ],
        ]);

        $this->template->setTranslation('title', 'en', $validated['titleEn']);
        $this->template->setTranslation('title', 'fa', $validated['titleFa'] ?? '');
        $this->template->setTranslation('summary', 'en', $validated['summaryEn'] ?? '');
        $this->template->setTranslation('summary', 'fa', $validated['summaryFa'] ?? '');
        $this->template->setTranslation('description', 'en', $validated['descriptionEn'] ?? '');
        $this->template->setTranslation('description', 'fa', $validated['descriptionFa'] ?? '');
        $this->template->save();

        $this->template->refresh();
        $this->hydrateDetailsFromTemplate();

        if ($status === MissionTemplateStatus::Published) {
            $this->adminFlash(__('admin.mission_engine.published_success'));
        } else {
            $this->adminFlash(__('admin.mission_engine.saved'));
        }
    }

    public function fillCapabilityConfigExample(int $capabilityTypeId, string $capabilityKey): void
    {
        $this->authorizeMissionAdmin();

        $key = MissionCapabilityKey::tryFrom($capabilityKey);

        if ($key === null) {
            return;
        }

        $example = MissionCapabilityConfigExamples::jsonFor($key);

        if ($example !== null) {
            $this->capabilityConfigJson[$capabilityTypeId] = $example;
        }
    }

    public function saveCapabilities(MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $validated = $this->validate([
            'enabledCapabilityIds' => ['array'],
            'enabledCapabilityIds.*' => ['integer', Rule::exists('mission_capability_types', 'id')],
            'capabilityConfigJson' => ['array'],
            'capabilityConfigJson.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $configs = [];

        foreach ($validated['enabledCapabilityIds'] as $capabilityTypeId) {
            $json = trim((string) ($this->capabilityConfigJson[$capabilityTypeId] ?? ''));

            if ($json === '') {
                continue;
            }

            $decoded = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw ValidationException::withMessages([
                    "capabilityConfigJson.{$capabilityTypeId}" => __('admin.mission_engine.invalid_json'),
                ]);
            }

            $configs[(int) $capabilityTypeId] = $decoded;
        }

        $admin->syncCapabilities($this->template, $validated['enabledCapabilityIds'], $configs);
        $this->template->load(['capabilities.capabilityType']);
        $this->hydrateCapabilityState();
        $this->adminFlash(__('admin.mission_engine.capabilities_saved'));
    }

    public function addField(MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $admin->createField($this->template, [
            'field_key' => 'new_field',
            'field_type' => MissionFieldType::Text->value,
            'label_en' => __('admin.mission_engine.new_field_label'),
            'section' => 'general',
        ]);

        $this->refreshFieldDrafts();
        $this->activeTab = 'fields';
        $this->adminFlash(__('admin.mission_engine.field_added'));
    }

    public function saveField(int $fieldId, MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $draft = collect($this->fieldDrafts)->firstWhere('id', $fieldId);

        if ($draft === null) {
            return;
        }

        $field = MissionTemplateField::query()
            ->where('template_id', $this->template->id)
            ->findOrFail($fieldId);

        $admin->updateField($field, $draft);
        $this->refreshFieldDrafts();
        $this->adminFlash(__('admin.mission_engine.field_saved'));
    }

    public function deleteField(int $fieldId, MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $field = MissionTemplateField::query()
            ->where('template_id', $this->template->id)
            ->findOrFail($fieldId);

        $admin->deleteField($field);
        $this->refreshFieldDrafts();
        $this->adminFlash(__('admin.mission_engine.field_deleted'));
    }

    public function moveField(int $fieldId, string $direction, MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $field = MissionTemplateField::query()
            ->where('template_id', $this->template->id)
            ->findOrFail($fieldId);

        $admin->moveField($field, $direction);
        $this->refreshFieldDrafts();
    }

    public function addPhase(MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $admin->createPhase($this->template, [
            'slug' => 'phase',
            'title_en' => __('admin.mission_engine.new_phase_label'),
            'duration_days' => 14,
        ]);

        $this->refreshPhaseDrafts();
        $this->activeTab = 'phases';
        $this->adminFlash(__('admin.mission_engine.phase_added'));
    }

    public function savePhase(int $phaseId, MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $draft = collect($this->phaseDrafts)->firstWhere('id', $phaseId);

        if ($draft === null) {
            return;
        }

        $phase = MissionTemplatePhase::query()
            ->where('template_id', $this->template->id)
            ->findOrFail($phaseId);

        $admin->updatePhase($phase, $draft);
        $this->refreshPhaseDrafts();
        $this->adminFlash(__('admin.mission_engine.phase_saved'));
    }

    public function deletePhase(int $phaseId, MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $phase = MissionTemplatePhase::query()
            ->where('template_id', $this->template->id)
            ->findOrFail($phaseId);

        $admin->deletePhase($phase);
        $this->refreshPhaseDrafts();
        $this->adminFlash(__('admin.mission_engine.phase_deleted'));
    }

    public function movePhase(int $phaseId, string $direction, MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $phase = MissionTemplatePhase::query()
            ->where('template_id', $this->template->id)
            ->findOrFail($phaseId);

        $admin->movePhase($phase, $direction);
        $this->refreshPhaseDrafts();
    }

    public function duplicateTemplate(MissionTemplateAdminService $admin): void
    {
        $this->authorizeMissionAdmin();

        $copy = $admin->duplicate($this->template, auth()->id());

        $this->redirect(route('admin.mission-engine.templates.edit', $copy), navigate: true);
    }

    public function render(): View
    {
        $locale = LocaleConfig::default();
        $readiness = app(MissionTemplateAdminService::class)->publishReadiness($this->template);

        return $this->adminView('livewire.admin.mission-engine.templates.edit', [
            'categories' => MissionCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'capabilityTypes' => MissionCapabilityType::query()->orderBy('sort_order')->get(),
            'difficultyOptions' => MissionDifficulty::cases(),
            'statusOptions' => MissionTemplateStatus::cases(),
            'fieldTypeOptions' => MissionFieldType::cases(),
            'sectionOptions' => $this->sectionOptions(),
            'enrollmentsCount' => $this->template->enrollments()->count(),
            'fieldsCount' => count($this->fieldDrafts),
            'phasesCount' => count($this->phaseDrafts),
            'readiness' => $readiness,
            'catalogPreviewUrl' => $this->template->isPublished()
                ? route('missions.show', ['locale' => $locale, 'template' => $this->template->slug])
                : null,
            'enrollments' => $this->activeTab === 'enrollments'
                ? $this->template->enrollments()->with('user')->latest()->paginate(15)
                : null,
        ], 'mission-engine');
    }

    private function authorizeMissionAdmin(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function detailsRules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', Rule::unique('mission_templates', 'slug')->ignore($this->template->id)],
            'titleEn' => ['required', 'string', 'max:500'],
            'titleFa' => ['nullable', 'string', 'max:500'],
            'summaryEn' => ['nullable', 'string', 'max:2000'],
            'summaryFa' => ['nullable', 'string', 'max:2000'],
            'descriptionEn' => ['nullable', 'string', 'max:10000'],
            'descriptionFa' => ['nullable', 'string', 'max:10000'],
            'categoryId' => ['nullable', 'integer', Rule::exists('mission_categories', 'id')],
            'difficulty' => ['required', Rule::enum(MissionDifficulty::class)],
            'estimatedDays' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'status' => ['required', Rule::enum(MissionTemplateStatus::class)],
            'isFeatured' => ['boolean'],
            'icon' => ['nullable', 'string', 'max:80'],
            'sortOrder' => ['integer', 'min:0', 'max:9999'],
            'ghostModeRecommended' => ['boolean'],
            'accent' => ['required', 'string', 'max:32'],
        ];
    }

    private function normalizeDetailsForm(): void
    {
        if ($this->categoryId === '' || $this->categoryId === 0) {
            $this->categoryId = null;
        }

        if ($this->estimatedDays === '' || $this->estimatedDays === 0) {
            $this->estimatedDays = null;
        }
    }

    private function hydrateDetailsFromTemplate(): void
    {
        $template = $this->template;
        $meta = is_array($template->meta) ? $template->meta : [];

        $this->slug = $template->slug;
        $this->titleEn = (string) $template->getTranslation('title', 'en', true);
        $this->titleFa = (string) $template->getTranslation('title', 'fa', true);
        $this->summaryEn = (string) ($template->getTranslation('summary', 'en', true) ?? '');
        $this->summaryFa = (string) ($template->getTranslation('summary', 'fa', true) ?? '');
        $this->descriptionEn = (string) ($template->getTranslation('description', 'en', true) ?? '');
        $this->descriptionFa = (string) ($template->getTranslation('description', 'fa', true) ?? '');
        $this->categoryId = $template->category_id;
        $this->difficulty = $template->difficulty->value;
        $this->estimatedDays = $template->estimated_days;
        $this->status = $template->status->value;
        $this->isFeatured = $template->is_featured;
        $this->icon = (string) ($template->icon ?? '');
        $this->sortOrder = (int) $template->sort_order;
        $this->ghostModeRecommended = (bool) ($meta['ghost_mode_recommended'] ?? false);
        $this->accent = (string) ($meta['accent'] ?? 'emerald');
    }

    private function hydrateCapabilityState(): void
    {
        $this->enabledCapabilityIds = $this->template->capabilities
            ->where('is_enabled', true)
            ->pluck('capability_type_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $this->capabilityConfigJson = [];

        foreach ($this->template->capabilities as $capability) {
            $config = $capability->config;

            $this->capabilityConfigJson[(int) $capability->capability_type_id] = is_array($config) && $config !== []
                ? json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : '';
        }
    }

    private function refreshFieldDrafts(): void
    {
        $this->fieldDrafts = $this->template->fields()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MissionTemplateField $field): array => [
                'id' => $field->id,
                'field_key' => $field->field_key,
                'field_type' => $field->field_type->value,
                'section' => (string) ($field->section ?? ''),
                'capability_type_id' => $field->capability_type_id,
                'label_en' => (string) $field->getTranslation('label', 'en', true),
                'label_fa' => (string) $field->getTranslation('label', 'fa', true),
                'help_en' => (string) ($field->getTranslation('help_text', 'en', true) ?? ''),
                'help_fa' => (string) ($field->getTranslation('help_text', 'fa', true) ?? ''),
                'default_value_json' => $this->encodeJsonForForm($field->default_value),
                'options_json' => $this->encodeJsonForForm($field->options),
                'sort_order' => $field->sort_order,
                'is_required' => $field->is_required,
            ])
            ->values()
            ->all();
    }

    private function refreshPhaseDrafts(): void
    {
        $this->phaseDrafts = $this->template->phases()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MissionTemplatePhase $phase): array => [
                'id' => $phase->id,
                'slug' => $phase->slug,
                'title_en' => (string) $phase->getTranslation('title', 'en', true),
                'title_fa' => (string) $phase->getTranslation('title', 'fa', true),
                'description_en' => (string) ($phase->getTranslation('description', 'en', true) ?? ''),
                'description_fa' => (string) ($phase->getTranslation('description', 'fa', true) ?? ''),
                'duration_days' => $phase->duration_days,
                'required_completion_count' => $phase->required_completion_count,
                'sort_order' => $phase->sort_order,
            ])
            ->values()
            ->all();
    }

    private function encodeJsonForForm(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @return list<string>
     */
    private function sectionOptions(): array
    {
        return [
            'schedule',
            'workout',
            'nutrition',
            'supplements',
            'equipment',
            'registration',
            'daily',
            'general',
        ];
    }
}
