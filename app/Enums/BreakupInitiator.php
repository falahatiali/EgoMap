<?php

namespace App\Enums;

enum BreakupInitiator: string
{
    case Me = 'me';
    case Them = 'them';
    case Mutual = 'mutual';

    public function label(): string
    {
        return match ($this) {
            self::Me => __('recovery.initiator_me'),
            self::Them => __('recovery.initiator_them'),
            self::Mutual => __('recovery.initiator_mutual'),
        };
    }
}
