<?php

namespace App\Enums;

enum PrimaryStruggle: string
{
    case Stalking = 'stalking';
    case Worthless = 'worthless';
    case Anger = 'anger';
    case GetBack = 'get_back';

    public function label(): string
    {
        return match ($this) {
            self::Stalking => __('recovery.struggle_stalking'),
            self::Worthless => __('recovery.struggle_worthless'),
            self::Anger => __('recovery.struggle_anger'),
            self::GetBack => __('recovery.struggle_get_back'),
        };
    }
}
