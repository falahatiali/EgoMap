<?php

namespace App\Livewire\Admin\Roles;

use App\Enums\Permission as PermissionEnum;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Support\AdminPermissionGroups;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Edit extends Component
{
    use WithAdminPage;

    public Role $role;

    /** @var list<string> */
    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        abort_unless(auth()->user()?->can(PermissionEnum::AdminRolesManage->value), 403);

        $this->role = $role;
        $this->selectedPermissions = $role->permissions->pluck('name')->sort()->values()->all();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can(PermissionEnum::AdminRolesManage->value), 403);

        $allowed = PermissionEnum::values();

        $this->validate([
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string', Rule::in($allowed)],
        ]);

        $this->role->syncPermissions($this->selectedPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->adminFlash(__('admin.roles.saved'));
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.roles.edit', [
            'groups' => AdminPermissionGroups::all(),
            'usersCount' => $this->role->users()->count(),
        ], 'roles');
    }
}
