<?php

namespace Modules\GamificationEngine\Services;

use Carbon\CarbonImmutable;
use Modules\GamificationEngine\Enums\GamificationRuleType;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationRule;
use Modules\GamificationEngine\Models\GamificationTransaction;
use Modules\GamificationEngine\Models\GamificationWallet;

/**
 * Aggregated metrics for the admin analytics dashboard.
 */
class GamificationAnalyticsService
{
    /**
     * Points/coins awarded per calendar day (positive deltas only).
     *
     * @return list<array{date: string, points: int, coins: int}>
     */
    public function dailyAwards(int $days = 30): array
    {
        $since = CarbonImmutable::today()->subDays($days - 1);

        $rows = GamificationTransaction::query()
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(CASE WHEN points_delta > 0 THEN points_delta ELSE 0 END) as points')
            ->selectRaw('SUM(CASE WHEN coins_delta > 0 THEN coins_delta ELSE 0 END) as coins')
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $indexed = $rows->keyBy('day');

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->addDays($i)->toDateString();
            $row = $indexed->get($date);

            $result[] = [
                'date' => $date,
                'points' => (int) ($row->points ?? 0),
                'coins' => (int) ($row->coins ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{user_id: int, name: string, email: string, points: int, coins: int, level: int}>
     */
    public function topUsers(int $limit = 10): array
    {
        return GamificationWallet::query()
            ->whereNotNull('user_id')
            ->with('user:id,name,email')
            ->orderByDesc('points')
            ->orderByDesc('coins')
            ->limit($limit)
            ->get()
            ->map(fn (GamificationWallet $wallet): array => [
                'user_id' => (int) $wallet->user_id,
                'name' => $wallet->user?->name ?? '—',
                'email' => $wallet->user?->email ?? '—',
                'points' => $wallet->points,
                'coins' => $wallet->coins,
                'level' => $wallet->level,
            ])
            ->all();
    }

    /**
     * @return list<array{rule_key: string, count: int}>
     */
    public function mostTriggeredRules(int $limit = 10): array
    {
        $rows = GamificationTransaction::query()
            ->whereNotNull('gamification_rule_id')
            ->selectRaw('gamification_rule_id, COUNT(*) as total')
            ->groupBy('gamification_rule_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $rules = GamificationRule::query()
            ->whereIn('id', $rows->pluck('gamification_rule_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(fn ($row): array => [
                'rule_key' => $rules->get($row->gamification_rule_id)?->key ?? 'unknown',
                'rule_name' => $rules->get($row->gamification_rule_id)?->name ?? 'unknown',
                'count' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return list<array{slug: string, name: string, count: int}>
     */
    public function mostEarnedBadges(int $limit = 10): array
    {
        $badges = GamificationBadge::query()->where('is_active', true)->get()->keyBy('slug');
        $counts = [];

        GamificationWallet::query()
            ->select(['badges'])
            ->whereNotNull('badges')
            ->chunk(200, function ($wallets) use (&$counts): void {
                foreach ($wallets as $wallet) {
                    foreach ($wallet->badgeSlugs() as $slug) {
                        $counts[$slug] = ($counts[$slug] ?? 0) + 1;
                    }
                }
            });

        arsort($counts);
        $counts = array_slice($counts, 0, $limit, true);

        return collect($counts)
            ->map(fn (int $count, string $slug): array => [
                'slug' => $slug,
                'name' => $badges->get($slug)?->name ?? $slug,
                'count' => $count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{wallets: int, transactions: int, total_points: int, total_coins: int, reward_rules: int, penalty_rules: int, points_awarded: int, points_penalized: int}
     */
    public function summary(): array
    {
        $pointsAwarded = (int) GamificationTransaction::query()->where('points_delta', '>', 0)->sum('points_delta');
        $pointsPenalized = abs((int) GamificationTransaction::query()->where('points_delta', '<', 0)->sum('points_delta'));

        return [
            'wallets' => GamificationWallet::query()->count(),
            'transactions' => GamificationTransaction::query()->count(),
            'total_points' => (int) GamificationWallet::query()->sum('points'),
            'total_coins' => (int) GamificationWallet::query()->sum('coins'),
            'reward_rules' => GamificationRule::query()
                ->where('rule_type', GamificationRuleType::Reward)
                ->where('is_active', true)
                ->count(),
            'penalty_rules' => GamificationRule::query()
                ->where('rule_type', GamificationRuleType::Penalty)
                ->where('is_active', true)
                ->count(),
            'points_awarded' => $pointsAwarded,
            'points_penalized' => $pointsPenalized,
        ];
    }

    /**
     * @return list<array{slug: string, earned_count: int}>
     */
    public function badgeEarnedCounts(): array
    {
        $counts = [];

        GamificationWallet::query()
            ->select(['badges'])
            ->whereNotNull('badges')
            ->chunk(200, function ($wallets) use (&$counts): void {
                foreach ($wallets as $wallet) {
                    foreach ($wallet->badgeSlugs() as $slug) {
                        $counts[$slug] = ($counts[$slug] ?? 0) + 1;
                    }
                }
            });

        return collect($counts)
            ->map(fn (int $count, string $slug): array => ['slug' => $slug, 'earned_count' => $count])
            ->values()
            ->all();
    }
}
