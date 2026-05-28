<?php

namespace App\Livewire\Admin\Roles;

use App\Enums\Permission as PermissionEnum;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithAdminPage;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(PermissionEnum::AdminRolesManage->value), 403);
    }

    public function render(): View
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount('permissions', 'users')
            ->orderBy('name')
            ->get();

        return $this->adminView('livewire.admin.roles.index', [
            'roles' => $roles,
        ], 'roles');
    }
}
