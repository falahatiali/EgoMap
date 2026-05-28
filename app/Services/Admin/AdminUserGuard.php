<?php

namespace App\Services\Admin;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminUserGuard
{
    public function canManageRoles(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function canAssignRole(User $actor, string $roleName): bool
    {
        if ($roleName === RoleName::SuperAdmin->value) {
            return $actor->isSuperAdmin();
        }

        return $actor->isAdmin();
    }

    public function canDelete(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($target->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        if ($target->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return false;
        }

        return $actor->isAdmin();
    }

    public function canDemoteSuperAdmin(User $actor, User $target): bool
    {
        if (! $target->isSuperAdmin()) {
            return true;
        }

        if ($actor->id === $target->id && $this->superAdminCount() <= 1) {
            return false;
        }

        return $actor->isSuperAdmin();
    }

    private function superAdminCount(): int
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::SuperAdmin->value))
            ->count();
    }

    public function actor(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
