<?php

namespace Modules\GamificationEngine\Enums;

enum GamificationPunishmentType: string
{
    case Physical = 'physical';
    case Mental = 'mental';
    case Time = 'time';
    case Writing = 'writing';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
