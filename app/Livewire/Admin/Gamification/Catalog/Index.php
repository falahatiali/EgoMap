<?php

namespace App\Livewire\Admin\Gamification\Catalog;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\GamificationEngine\Services\GamificationCatalogService;

/**
 * Admin overview: all rewards, penalties, events map, and service API reference.
 */
class Index extends Component
{
    use WithAdminPage;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
    }

    public function render(GamificationCatalogService $catalog): View
    {
        return $this->adminView('livewire.admin.gamification.catalog.index', [
            'overview' => $catalog->overview(),
            'rewards' => $catalog->rewards(),
            'penalties' => $catalog->penalties(),
            'eventsMap' => $catalog->eventsMap(),
            'apiReference' => $catalog->serviceApiReference(),
            'effectFields' => $catalog->effectFieldReference(),
            'activeGamificationNav' => 'catalog',
        ], 'gamification');
    }
}
