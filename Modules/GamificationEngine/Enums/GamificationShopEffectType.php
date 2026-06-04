<?php

namespace Modules\GamificationEngine\Enums;

/** Phoenix Shop purchase effect handlers in GamificationEngine::applyShopEffect. */
enum GamificationShopEffectType: string
{
    /** Adds streak_freeze_charges to wallet.metadata. */
    case StreakFreeze = 'streak_freeze';
    case ShieldRepair = 'shield_repair';
    case EmergencyBoost = 'emergency_boost';
    case GrantPerk = 'grant_perk';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
