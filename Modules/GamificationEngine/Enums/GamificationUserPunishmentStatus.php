<?php

namespace Modules\GamificationEngine\Enums;

enum GamificationUserPunishmentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Expired = 'expired';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
