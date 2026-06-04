<?php

namespace App\Livewire\Admin\Gamification\Rules;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\GamificationEngine\Enums\GamificationRuleType;
use Modules\GamificationEngine\Models\GamificationRule;

/**
 * Paginated list of reward/penalty rules with filters.
 */
class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $eventFilter = '';

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

    public function toggleActive(int $ruleId): void
    {
        $rule = GamificationRule::query()->findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);
        $this->adminFlash(__('admin.gamification.rule_saved'));
    }

    public function render(): View
    {
        $rules = GamificationRule::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('key', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('event', 'like', $term);
                });
            })
            ->when($this->eventFilter !== '', fn ($query) => $query->where('event', $this->eventFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('rule_type', $this->typeFilter))
            ->orderBy('event')
            ->orderBy('priority')
            ->paginate(20);

        $events = GamificationRule::query()->distinct()->orderBy('event')->pluck('event');

        return $this->adminView('livewire.admin.gamification.rules.index', [
            'rules' => $rules,
            'events' => $events,
            'ruleTypes' => GamificationRuleType::cases(),
            'activeGamificationNav' => 'rules',
        ], 'gamification');
    }
}
