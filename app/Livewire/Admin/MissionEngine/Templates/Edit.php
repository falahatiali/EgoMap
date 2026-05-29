<?php

namespace App\Livewire\Admin\MissionEngine\Templates;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\MissionEngine\Enums\MissionDifficulty;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionCapabilityType;
use Modules\MissionEngine\Models\MissionCategory;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionTemplateCapabilitySync;

class Edit extends Component
{
    use WithAdminPage;

    public MissionTemplate $template;

    public string $slug = '';

    public string $titleEn = '';

    public string $titleFa = '';

    public string $summaryEn = '';

    public string $descriptionEn = '';

    public ?int $categoryId = null;

    public string $difficulty = '';

    public ?int $estimatedDays = null;

    public string $status = '';

    public bool $isFeatured = false;

    /** @var list<int> */
    public array $enabledCapabilityIds = [];

    public function mount(MissionTemplate $template): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);

        $this->template = $template->load(['capabilities.capabilityType', 'category']);
        $this->slug = $template->slug;
        $this->titleEn = (string) $template->getTranslation('title', 'en', true);
        $this->titleFa = (string) $template->getTranslation('title', 'fa', true);
        $this->summaryEn = (string) ($template->getTranslation('summary', 'en', true) ?? '');
        $this->descriptionEn = (string) ($template->getTranslation('description', 'en', true) ?? '');
        $this->categoryId = $template->category_id;
        $this->difficulty = $template->difficulty->value;
        $this->estimatedDays = $template->estimated_days;
        $this->status = $template->status->value;
        $this->isFeatured = $template->is_featured;
        $this->enabledCapabilityIds = $template->capabilities
            ->where('is_enabled', true)
            ->pluck('capability_type_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function save(MissionTemplateCapabilitySync $capabilitySync): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);

        $this->slug = Str::slug($this->slug);

        $validated = $this->validate([
            'slug' => ['required', 'string', 'max:120', Rule::unique('mission_templates', 'slug')->ignore($this->template->id)],
            'titleEn' => ['required', 'string', 'max:500'],
            'titleFa' => ['nullable', 'string', 'max:500'],
            'summaryEn' => ['nullable', 'string', 'max:2000'],
            'descriptionEn' => ['nullable', 'string', 'max:10000'],
            'categoryId' => ['nullable', 'integer', Rule::exists('mission_categories', 'id')],
            'difficulty' => ['required', Rule::enum(MissionDifficulty::class)],
            'estimatedDays' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'status' => ['required', Rule::enum(MissionTemplateStatus::class)],
            'isFeatured' => ['boolean'],
            'enabledCapabilityIds' => ['array'],
            'enabledCapabilityIds.*' => ['integer', Rule::exists('mission_capability_types', 'id')],
        ]);

        $status = MissionTemplateStatus::from($validated['status']);
        $publishedAt = $this->template->published_at;

        if ($status === MissionTemplateStatus::Published && $publishedAt === null) {
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
        ]);

        $this->template->setTranslation('title', 'en', $validated['titleEn']);
        $this->template->setTranslation('title', 'fa', $validated['titleFa'] ?? '');
        $this->template->setTranslation('summary', 'en', $validated['summaryEn'] ?? '');
        $this->template->setTranslation('description', 'en', $validated['descriptionEn'] ?? '');
        $this->template->save();

        $capabilitySync->sync($this->template, $validated['enabledCapabilityIds']);

        $this->adminFlash(__('admin.mission_engine.saved'));
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.mission-engine.templates.edit', [
            'categories' => MissionCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'capabilityTypes' => MissionCapabilityType::query()->orderBy('sort_order')->get(),
            'difficultyOptions' => MissionDifficulty::cases(),
            'statusOptions' => MissionTemplateStatus::cases(),
            'enrollmentsCount' => $this->template->enrollments()->count(),
            'fieldsCount' => $this->template->fields()->count(),
            'phasesCount' => $this->template->phases()->count(),
        ], 'mission-engine');
    }
}
