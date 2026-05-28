<?php

namespace App\Livewire\Admin\Permissions;

use App\Enums\Permission as PermissionEnum;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Support\AdminPermissionGroups;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithAdminPage;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(PermissionEnum::AdminRolesManage->value), 403);
    }

    public function render(): View
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return $this->adminView('livewire.admin.permissions.index', [
            'permissions' => $permissions,
            'roles' => $roles,
            'groups' => AdminPermissionGroups::all(),
        ], 'permissions');
    }
}
