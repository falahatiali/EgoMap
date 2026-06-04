<?php

namespace Modules\MissionEngine\Support;

use App\Enums\RoleName;
use App\Models\User;

final class MissionProGate
{
    public static function userHasProAccess(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasRole(RoleName::Pro->value)
            || $user->hasRole(RoleName::SuperAdmin->value)
            || $user->hasRole(RoleName::Admin->value);
    }

    /**
     * @param  array<string, mixed>  $capabilityConfig
     */
    public static function featureRequiresPro(array $capabilityConfig, string $featureKey): bool
    {
        return (bool) data_get($capabilityConfig, "features.{$featureKey}.requires_pro", false);
    }

    /**
     * @param  array<string, mixed>  $capabilityConfig
     */
    public static function canUseFeature(?User $user, array $capabilityConfig, string $featureKey): bool
    {
        if (! self::featureRequiresPro($capabilityConfig, $featureKey)) {
            return true;
        }

        return self::userHasProAccess($user);
    }
}
