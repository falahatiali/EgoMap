<?php

namespace App\Livewire\Admin\Users;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Livewire\Admin\Concerns\WithAdminPage;
use App\Models\User;
use App\Services\Admin\AdminUserGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Edit extends Component
{
    use WithAdminPage;

    public User $user;

    public string $name = '';

    public string $email = '';

    public bool $emailVerified = false;

    /** @var list<string> */
    public array $selectedRoles = [];

    public string $password = '';

    public bool $confirmDelete = false;

    public function mount(User $user): void
    {
        abort_unless(auth()->user()?->can(\App\Enums\Permission::AdminUsersManage->value), 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->emailVerified = $user->email_verified_at !== null;
        $this->selectedRoles = $user->roles->pluck('name')->all();
    }

    public function save(AdminUserGuard $guard): void
    {
        $this->authorizeUser();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::in(RoleName::values())],
            'password' => ['nullable', 'string', Password::defaults()],
        ]);

        foreach ($this->selectedRoles as $roleName) {
            abort_unless($guard->canAssignRole($guard->actor(), $roleName), 403);
        }

        if (! $guard->canDemoteSuperAdmin($guard->actor(), $this->user) && ! in_array(RoleName::SuperAdmin->value, $this->selectedRoles, true)) {
            $this->addError('selectedRoles', __('admin.users.cannot_remove_last_super_admin'));

            return;
        }

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->emailVerified ? ($this->user->email_verified_at ?? now()) : null,
        ]);

        if ($this->password !== '') {
            $this->user->update(['password' => Hash::make($this->password)]);
        }

        $this->user->syncRoles($this->selectedRoles);

        $this->password = '';
        $this->user->refresh()->load('roles');
        $this->selectedRoles = $this->user->roles->pluck('name')->all();

        $this->adminFlash(__('admin.users.saved'));
    }

    public function delete(AdminUserGuard $guard): void
    {
        $this->authorizeUser();

        if (! $this->confirmDelete) {
            $this->confirmDelete = true;

            return;
        }

        abort_unless($guard->canDelete($guard->actor(), $this->user), 403);

        $this->user->delete();

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function cancelDelete(): void
    {
        $this->confirmDelete = false;
    }

    public function render(AdminUserGuard $guard): View
    {
        return $this->adminView('livewire.admin.users.edit', [
            'roleOptions' => RoleName::values(),
            'canManageRoles' => $guard->canManageRoles($guard->actor()),
            'canDelete' => $guard->canDelete($guard->actor(), $this->user),
        ], 'users');
    }

    private function authorizeUser(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminUsersManage->value), 403);
    }
}
