<?php

namespace Modules\GamificationEngine\Enums;

enum GamificationPerkType: string
{
    case Consumable = 'consumable';
    case Permanent = 'permanent';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
