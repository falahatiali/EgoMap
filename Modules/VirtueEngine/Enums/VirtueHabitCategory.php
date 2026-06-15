<?php

namespace Modules\VirtueEngine\Enums;

enum VirtueHabitCategory: string
{
    case Communication = 'communication';
    case Emotional = 'emotional';
    case Social = 'social';
    case Internal = 'internal';
    case Procrastination = 'procrastination';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Communication => 'Communication',
            self::Emotional => 'Emotional Control',
            self::Social => 'Social Behaviour',
            self::Internal => 'Inner Mindset',
            self::Procrastination => 'Procrastination',
            self::Custom => 'Custom',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Communication => '💬',
            self::Emotional => '🧘',
            self::Social => '🤝',
            self::Internal => '🧠',
            self::Procrastination => '⏰',
            self::Custom => '✨',
        };
    }
}
