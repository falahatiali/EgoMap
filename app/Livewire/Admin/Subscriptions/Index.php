<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Services\Billing\AdminSubscriptionListingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminUsersManage->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(AdminSubscriptionListingService $listing): View
    {
        return $this->adminView('livewire.admin.subscriptions.index', [
            'subscriptions' => $listing->paginate(
                search: $this->search !== '' ? $this->search : null,
                statusFilter: $this->statusFilter,
            ),
            'activeCount' => $listing->activeCount(),
        ], 'subscriptions');
    }
}
