<?php

namespace App\Livewire\Admin\Gamification\Shop;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\GamificationEngine\Models\GamificationShopItem;

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
        return $this->adminView('livewire.admin.gamification.shop.index', [
            'items' => GamificationShopItem::query()->orderBy('sort_order')->paginate(20),
            'activeGamificationNav' => 'shop',
        ], 'gamification');
    }
}
