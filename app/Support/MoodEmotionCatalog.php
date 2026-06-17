<?php

namespace App\Support;

use App\Enums\MoodEmotion;

class MoodEmotionCatalog
{
    /**
     * @return list<array{value: string, label: string, emoji: string, color: string}>
     */
    public static function options(string $locale): array
    {
        return collect(MoodEmotion::cases())->map(fn (MoodEmotion $emotion): array => [
            'value' => $emotion->value,
            'label' => __('mood.emotions.'.$emotion->value, locale: $locale),
            'emoji' => self::emoji($emotion),
            'color' => self::color($emotion),
        ])->values()->all();
    }

    public static function emoji(MoodEmotion $emotion): string
    {
        return match ($emotion) {
            MoodEmotion::Joy => '✨',
            MoodEmotion::Sadness => '🌧️',
            MoodEmotion::Anger => '🔥',
            MoodEmotion::Fear => '🌫️',
            MoodEmotion::Energy => '⚡',
            MoodEmotion::Calm => '🌿',
        };
    }

    public static function color(MoodEmotion $emotion): string
    {
        return match ($emotion) {
            MoodEmotion::Joy => '#FBBF24',
            MoodEmotion::Sadness => '#60A5FA',
            MoodEmotion::Anger => '#F87171',
            MoodEmotion::Fear => '#A78BFA',
            MoodEmotion::Energy => '#FB923C',
            MoodEmotion::Calm => '#34D399',
        };
    }
}
