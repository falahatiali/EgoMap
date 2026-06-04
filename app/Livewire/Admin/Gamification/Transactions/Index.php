<?php

namespace App\Livewire\Admin\Gamification\Transactions;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\GamificationEngine\Models\GamificationTransaction;

/**
 * Ledger of all wallet changes for auditing rewards and penalties.
 */
class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $eventFilter = '';

    public string $search = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $transactions = GamificationTransaction::query()
            ->with(['wallet.user:id,name,email', 'rule:id,key,name,rule_type'])
            ->when($this->eventFilter !== '', fn ($q) => $q->where('event', $this->eventFilter))
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('event', 'like', $term)
                        ->orWhereHas('wallet.user', fn ($u) => $u->where('email', 'like', $term)->orWhere('name', 'like', $term));
                });
            })
            ->latest('created_at')
            ->paginate(25);

        $events = GamificationTransaction::query()->distinct()->orderBy('event')->pluck('event');

        return $this->adminView('livewire.admin.gamification.transactions.index', [
            'transactions' => $transactions,
            'events' => $events,
            'activeGamificationNav' => 'transactions',
        ], 'gamification');
    }
}
