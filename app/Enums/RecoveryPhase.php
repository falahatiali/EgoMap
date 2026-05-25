<?php

namespace App\Enums;

enum RecoveryPhase: string
{
    case Diagnose = 'diagnose';
    case Detox = 'detox';
    case Deliver = 'deliver';

    public function order(): int
    {
        return match ($this) {
            self::Diagnose => 1,
            self::Detox => 2,
            self::Deliver => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Diagnose => __('recovery.phase_diagnose'),
            self::Detox => __('recovery.phase_detox'),
            self::Deliver => __('recovery.phase_deliver'),
        };
    }
}
