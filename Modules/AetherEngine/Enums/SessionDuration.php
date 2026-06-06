<?php

namespace Modules\AetherEngine\Enums;

enum SessionDuration: string
{
    case TenToTwenty = '10_20';
    case TwentyToThirty = '20_30';
    case ThirtyToFortyFive = '30_45';
    case FortyFiveToSixty = '45_60';
    case SixtyPlus = '60_plus';

    public function maxMinutes(): int
    {
        return match ($this) {
            self::TenToTwenty => 20,
            self::TwentyToThirty => 30,
            self::ThirtyToFortyFive => 45,
            self::FortyFiveToSixty => 60,
            self::SixtyPlus => 90,
        };
    }
}
