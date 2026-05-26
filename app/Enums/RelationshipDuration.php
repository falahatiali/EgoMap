<?php

namespace App\Enums;

enum RelationshipDuration: string
{
    case UnderSixMonths = 'under_6_months';
    case SixToTwelve = 'six_to_twelve';
    case OneToThreeYears = 'one_to_three_years';
    case ThreePlusYears = 'three_plus_years';

    public function label(): string
    {
        return match ($this) {
            self::UnderSixMonths => __('recovery.rel_under_6_months'),
            self::SixToTwelve => __('recovery.rel_six_to_twelve'),
            self::OneToThreeYears => __('recovery.rel_one_to_three_years'),
            self::ThreePlusYears => __('recovery.rel_three_plus_years'),
        };
    }
}
