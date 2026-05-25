<?php

namespace App\Enums;

enum BreakupDuration: string
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';
    case Years = 'years';

    public function label(): string
    {
        return match ($this) {
            self::Days => __('recovery.duration_days'),
            self::Weeks => __('recovery.duration_weeks'),
            self::Months => __('recovery.duration_months'),
            self::Years => __('recovery.duration_years'),
        };
    }
}
