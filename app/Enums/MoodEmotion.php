<?php

namespace App\Enums;

enum MoodEmotion: string
{
    case Joy = 'joy';
    case Sadness = 'sadness';
    case Anger = 'anger';
    case Fear = 'fear';
    case Energy = 'energy';
    case Calm = 'calm';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
