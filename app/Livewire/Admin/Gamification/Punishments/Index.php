<?php

namespace App\Livewire\Admin\Gamification\Punishments;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\GamificationEngine\Models\GamificationPunishment;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $punishmentId): void
    {
        $punishment = GamificationPunishment::query()->findOrFail($punishmentId);
        $punishment->update(['is_active' => ! $punishment->is_active]);
        $this->adminFlash(__('admin.gamification.punishment_saved'));
    }

    public function render(): View
    {
        $punishments = GamificationPunishment::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('slug', 'like', $term)
                        ->orWhere('title', 'like', $term);
                });
            })
            ->when($this->typeFilter !== '', fn ($query) => $query->where('type', $this->typeFilter))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(20);

        return $this->adminView('livewire.admin.gamification.punishments.index', [
            'punishments' => $punishments,
            'activeGamificationNav' => 'punishments',
        ], 'gamification');
    }
}
