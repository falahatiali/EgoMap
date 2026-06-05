<?php

namespace App\Models\Concerns;

use App\Enums\Permission;
use App\Enums\RoleName;

trait InteractsWithEgoMapPermissions
{
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
        ]);
    }

    public function hasActiveSubscription(?string $type = null): bool
    {
        return $this->subscribed($type ?? (string) config('billing.subscription_name', 'default'));
    }

    public function isPro(): bool
    {
        if ($this->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Pro->value,
        ])) {
            return true;
        }

        if ($this->hasActiveSubscription()) {
            return true;
        }

        return $this->can(Permission::ReportsViewPremium->value);
    }

    public function isMember(): bool
    {
        return $this->hasRole(RoleName::Member->value);
    }
}
