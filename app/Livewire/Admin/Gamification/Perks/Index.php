<?php

namespace App\Livewire\Admin\Gamification\Perks;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\GamificationEngine\Models\GamificationPerk;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.perks.index', [
            'perks' => GamificationPerk::query()->orderBy('name')->paginate(20),
            'activeGamificationNav' => 'perks',
        ], 'gamification');
    }
}
