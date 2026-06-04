<?php

namespace App\Livewire\Admin\Gamification\Wallets;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\GamificationEngine\Services\GamificationEngine;

/**
 * Search user wallet and apply manual point/coin/XP adjustments.
 */
class Index extends Component
{
    use WithAdminPage;

    public string $email = '';

    public int $pointsDelta = 0;

    public int $coinsDelta = 0;

    public int $xpDelta = 0;

    public string $reason = '';

    /** @var array<string, mixed>|null */
    public ?array $wallet = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
    }

    public function search(GamificationEngine $engine): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $user = User::query()->where('email', $this->email)->first();

        if ($user === null) {
            $this->addError('email', __('admin.gamification.user_not_found'));
            $this->wallet = null;

            return;
        }

        $this->wallet = $engine->walletFor($user);
    }

    public function adjust(GamificationEngine $engine): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $this->email)->firstOrFail();

        $result = $engine->adjustWallet(
            $this->pointsDelta,
            $this->coinsDelta,
            $this->xpDelta,
            ['user_id' => $user->id, 'reason' => $this->reason],
        );

        $this->wallet = $result['wallet'];
        $this->adminFlash(__('admin.gamification.wallet_adjusted'));
        $this->pointsDelta = 0;
        $this->coinsDelta = 0;
        $this->xpDelta = 0;
        $this->reason = '';
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.wallets.index', [
            'activeGamificationNav' => 'wallets',
        ], 'gamification');
    }
}
