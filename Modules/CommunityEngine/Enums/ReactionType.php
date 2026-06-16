<?php

namespace Modules\CommunityEngine\Enums;

enum ReactionType: string
{
    case Like = 'like';
    case Love = 'love';
    case Fire = 'fire';
    case Support = 'support';
    case Insight = 'insight';
    case Strength = 'strength';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '❤️',
            self::Love => '😍',
            self::Fire => '🔥',
            self::Support => '🙌',
            self::Insight => '💡',
            self::Strength => '💪',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Like => 'Like',
            self::Love => 'Love',
            self::Fire => 'Fire',
            self::Support => 'Support',
            self::Insight => 'Insight',
            self::Strength => 'Strength',
        };
    }

    /**
     * @return list<array{type: string, emoji: string, label: string}>
     */
    public static function forUi(): array
    {
        return array_map(
            fn (self $case): array => [
                'type' => $case->value,
                'emoji' => $case->emoji(),
                'label' => $case->label(),
            ],
            self::cases(),
        );
    }
}
