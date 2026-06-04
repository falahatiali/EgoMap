<?php

namespace App\Livewire\Admin\MissionEngine\Templates;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
use Modules\MissionEngine\Models\MissionTemplate;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminMissionsManage->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $templates = MissionTemplate::query()
            ->with('category')
            ->withCount(['enrollments', 'fields', 'phases'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('slug', 'like', $term)
                        ->orWhere('title->en', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('updated_at')
            ->paginate(20);

        return $this->adminView('livewire.admin.mission-engine.templates.index', [
            'templates' => $templates,
            'statusOptions' => MissionTemplateStatus::cases(),
        ], 'mission-engine');
    }
}
