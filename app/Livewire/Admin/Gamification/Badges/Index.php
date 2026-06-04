<?php

namespace App\Livewire\Admin\Gamification\Badges;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationWallet;

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
        $earnedCounts = [];
        GamificationWallet::query()->whereNotNull('badges')->chunk(100, function ($wallets) use (&$earnedCounts): void {
            foreach ($wallets as $wallet) {
                foreach ($wallet->badgeSlugs() as $slug) {
                    $earnedCounts[$slug] = ($earnedCounts[$slug] ?? 0) + 1;
                }
            }
        });

        $badges = GamificationBadge::query()->orderBy('name')->paginate(20);

        return $this->adminView('livewire.admin.gamification.badges.index', [
            'badges' => $badges,
            'earnedCounts' => $earnedCounts,
            'activeGamificationNav' => 'badges',
        ], 'gamification');
    }
}
