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
use Modules\MissionEngine\Models\MissionCategory;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionTemplateCapabilitySync;

class Create extends Component
{
    use WithAdminPage;

    public string $slug = '';

    public string $titleEn = '';

    public string $summaryEn = '';

    public ?int $categoryId = null;

    public string $difficulty = '';

    public ?int $estimatedDays = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);

        $this->difficulty = MissionDifficulty::Beginner->value;
    }

    public function save(MissionTemplateCapabilitySync $capabilitySync): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);

        $this->slug = Str::slug($this->slug);

        $validated = $this->validate([
            'slug' => ['required', 'string', 'max:120', Rule::unique('mission_templates', 'slug')],
            'titleEn' => ['required', 'string', 'max:500'],
            'summaryEn' => ['nullable', 'string', 'max:2000'],
            'categoryId' => ['nullable', 'integer', Rule::exists('mission_categories', 'id')],
            'difficulty' => ['required', Rule::enum(MissionDifficulty::class)],
            'estimatedDays' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $template = MissionTemplate::query()->create([
            'slug' => $validated['slug'],
            'category_id' => $validated['categoryId'],
            'difficulty' => $validated['difficulty'],
            'estimated_days' => $validated['estimatedDays'],
            'status' => MissionTemplateStatus::Draft,
            'version' => 1,
            'created_by' => auth()->id(),
        ]);

        $template->setTranslation('title', 'en', $validated['titleEn']);
        $template->setTranslation('summary', 'en', $validated['summaryEn'] ?? '');
        $template->save();

        $capabilitySync->ensureAllCapabilitiesExist($template);

        $this->redirect(route('admin.mission-engine.templates.edit', $template), navigate: true);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.mission-engine.templates.create', [
            'categories' => MissionCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'difficultyOptions' => MissionDifficulty::cases(),
        ], 'mission-engine');
    }
}
