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
    case Sad = 'sad';
    case Hug = 'hug';
    case Heartbreak = 'heartbreak';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '❤️',
            self::Love => '😍',
            self::Fire => '🔥',
            self::Support => '🙌',
            self::Insight => '💡',
            self::Strength => '💪',
            self::Sad => '😢',
            self::Hug => '🤗',
            self::Heartbreak => '💔',
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
            self::Sad => 'Sad',
            self::Hug => 'Hug',
            self::Heartbreak => 'Heartbreak',
        };
    }

    /** @return 'positive'|'empathetic' */
    public function tone(): string
    {
        return match ($this) {
            self::Sad, self::Hug, self::Heartbreak => 'empathetic',
            default => 'positive',
        };
    }

    /**
     * @return list<array{type: string, emoji: string, label: string, tone: string}>
     */
    public static function forUi(): array
    {
        return array_map(
            fn (self $case): array => [
                'type' => $case->value,
                'emoji' => $case->emoji(),
                'label' => $case->label(),
                'tone' => $case->tone(),
            ],
            self::cases(),
        );
    }

    /**
     * @return array{positive: list<array{type: string, emoji: string, label: string, tone: string}>, empathetic: list<array{type: string, emoji: string, label: string, tone: string}>}
     */
    public static function forUiGrouped(): array
    {
        $grouped = ['positive' => [], 'empathetic' => []];

        foreach (self::forUi() as $reaction) {
            $grouped[$reaction['tone']][] = $reaction;
        }

        return $grouped;
    }
}
