<?php

namespace App\Livewire\Admin\Users;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithAdminPage;
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    protected string $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminUsersManage->value), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->roleFilter !== '', fn ($query) => $query->role($this->roleFilter))
            ->latest()
            ->paginate(20);

        return $this->adminView('livewire.admin.users.index', [
            'users' => $users,
            'roleOptions' => RoleName::values(),
        ], 'users');
    }
}
