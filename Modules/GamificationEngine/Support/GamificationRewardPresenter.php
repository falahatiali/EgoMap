<?php

namespace Modules\GamificationEngine\Support;

/**
 * Formats dispatch results for user-facing toasts and activity labels.
 */
class GamificationRewardPresenter
{
    /**
     * @param  array<string, mixed>  $payload  dispatch() return value
     * @return array{headline: string, subtitle: string, tone: string, points_delta: int, coins_delta: int, xp_delta: int, badges: list<string>, rules: list<string>}
     */
    public function formatToast(array $payload): array
    {
        $applied = is_array($payload['applied'] ?? null) ? $payload['applied'] : [];
        $points = (int) ($payload['points_delta'] ?? 0);
        $coins = (int) ($payload['coins_delta'] ?? 0);
        $xp = (int) ($payload['xp_delta'] ?? 0);
        $badges = is_array($payload['badges'] ?? null) ? $payload['badges'] : [];

        $tone = 'neutral';
        if ($points < 0 || $coins < 0) {
            $tone = 'penalty';
        } elseif ($points > 0 || $coins > 0 || $xp > 0 || $badges !== []) {
            $tone = 'reward';
        }

        $ruleNames = array_values(array_filter(array_map(
            fn (array $row): string => (string) ($row['rule_name'] ?? ''),
            $applied,
        )));

        $headline = match ($tone) {
            'penalty' => __('gamification.toast.headline_penalty'),
            'reward' => __('gamification.toast.headline_reward'),
            default => __('gamification.toast.headline_neutral'),
        };

        $parts = [];
        if ($points !== 0) {
            $parts[] = ($points > 0 ? '+' : '').$points.' '.__('gamification.stats.points');
        }
        if ($coins !== 0) {
            $parts[] = ($coins > 0 ? '+' : '').$coins.' '.__('gamification.stats.coins');
        }
        if ($xp !== 0) {
            $parts[] = ($xp > 0 ? '+' : '').$xp.' XP';
        }

        $subtitle = $parts !== [] ? implode(' · ', $parts) : ($payload['message'] ?? '');

        if ($ruleNames !== []) {
            $subtitle = $subtitle !== ''
                ? $subtitle.' — '.implode(', ', $ruleNames)
                : implode(', ', $ruleNames);
        }

        if ($badges !== []) {
            $subtitle .= ($subtitle !== '' ? ' · ' : '').__('gamification.toast.badge_unlocked', [
                'badge' => implode(', ', $badges),
            ]);
        }

        return [
            'headline' => $headline,
            'subtitle' => $subtitle,
            'tone' => $tone,
            'points_delta' => $points,
            'coins_delta' => $coins,
            'xp_delta' => $xp,
            'badges' => $badges,
            'rules' => $ruleNames,
        ];
    }

    public function levelNarrative(int $level): string
    {
        $narratives = __('gamification.level_narratives');

        if (is_array($narratives) && isset($narratives[$level]) && is_string($narratives[$level])) {
            return $narratives[$level];
        }

        $milestones = [1, 2, 3, 5, 10, 20, 50, 90];
        $chosen = 1;
        foreach ($milestones as $milestone) {
            if ($level >= $milestone) {
                $chosen = $milestone;
            }
        }

        if (is_array($narratives) && isset($narratives[$chosen]) && is_string($narratives[$chosen])) {
            return $narratives[$chosen];
        }

        return __('gamification.toast.level_fallback', ['level' => $level]);
    }
}
