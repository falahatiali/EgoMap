<?php

namespace App\Livewire\Admin\Gamification\Analytics;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\GamificationEngine\Services\GamificationAnalyticsService;

class Dashboard extends Component
{
    use WithAdminPage;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
    }

    public function render(GamificationAnalyticsService $analytics): View
    {
        return $this->adminView('livewire.admin.gamification.analytics.dashboard', [
            'summary' => $analytics->summary(),
            'dailyAwards' => $analytics->dailyAwards(30),
            'topUsers' => $analytics->topUsers(10),
            'topRules' => $analytics->mostTriggeredRules(10),
            'topBadges' => $analytics->mostEarnedBadges(10),
            'activeGamificationNav' => 'analytics',
        ], 'gamification');
    }
}
