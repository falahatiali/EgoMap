<?php

namespace App\Livewire\Admin;

use App\Services\Admin\AdminDashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * @return array{
     *     stats: array<string, int>,
     *     growth: array{users_today: int, sessions_today: int},
     *     recent_users: list<array{id: int, name: string, email: string, roles: list<string>, created_at: string}>,
     *     recent_sessions: list<array{uuid: string, status: string, quiz_name: string, user_label: string, updated_at: string}>
     * }
     */
    public function getSnapshotProperty(): array
    {
        return app(AdminDashboardService::class)->snapshot();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard', [
            'snapshot' => $this->snapshot,
        ])->layout('layouts.admin', [
            'activeNav' => 'dashboard',
        ]);
    }
}
