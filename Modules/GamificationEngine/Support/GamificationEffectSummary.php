<?php

namespace Modules\GamificationEngine\Support;

/**
 * Human-readable summaries of rule/shop effect JSON for admin UIs.
 */
class GamificationEffectSummary
{
    /**
     * @param  array<string, mixed>|null  $effects
     */
    public static function fromEffects(?array $effects): string
    {
        if (! is_array($effects) || $effects === []) {
            return '—';
        }

        $parts = [];

        foreach (['points', 'coins', 'xp'] as $key) {
            if (! array_key_exists($key, $effects)) {
                continue;
            }

            $value = (int) $effects[$key];
            if ($value === 0) {
                continue;
            }

            $sign = $value > 0 ? '+' : '';
            $parts[] = $sign.$value.' '.$key;
        }

        if (isset($effects['badge']) && is_string($effects['badge']) && $effects['badge'] !== '') {
            $parts[] = 'badge:'.$effects['badge'];
        }

        if (isset($effects['perk']) && is_string($effects['perk']) && $effects['perk'] !== '') {
            $parts[] = 'perk:'.$effects['perk'];
        }

        if ($effects['increment_streak'] ?? false) {
            $parts[] = 'streak+1';
        }

        if ($effects['reset_streak'] ?? false) {
            $parts[] = 'streak reset';
        }

        if (isset($effects['metadata']) && is_array($effects['metadata'])) {
            $parts[] = 'metadata merge';
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }
}
