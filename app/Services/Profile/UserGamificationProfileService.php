<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationPerk;
use Modules\GamificationEngine\Models\GamificationTransaction;
use Modules\GamificationEngine\Models\GamificationUserPunishment;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Enums\GamificationUserPunishmentStatus;

/**
 * Read-only gamification dossier for the profile rewards page.
 */
readonly class UserGamificationProfileService
{
    public function __construct(
        private GamificationEngine $gamification,
    ) {}

    /**
     * @return array{
     *     has_wallet: bool,
     *     wallet: array<string, mixed>,
     *     totals: array{transactions: int, rewards: int, penalties: int, punishments_completed: int, punishments_pending: int},
     *     transactions: list<array<string, mixed>>,
     *     punishments: list<array<string, mixed>>,
     *     badges: list<array<string, mixed>>,
     *     perks: list<array<string, mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $walletModel = GamificationWallet::query()->where('user_id', $user->id)->first();

        if ($walletModel === null) {
            return $this->emptyState();
        }

        $wallet = $this->gamification->walletFor($user, null);
        $badgeCatalog = GamificationBadge::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('slug');
        $perkCatalog = GamificationPerk::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('slug');

        $transactions = $walletModel->transactions()
            ->with('rule')
            ->latest('created_at')
            ->limit(40)
            ->get()
            ->map(fn (GamificationTransaction $tx): array => $this->formatTransaction($tx))
            ->all();

        $punishments = GamificationUserPunishment::query()
            ->with('punishment')
            ->where('user_id', $user->id)
            ->latest('assigned_at')
            ->limit(20)
            ->get()
            ->map(fn (GamificationUserPunishment $row): array => $this->formatUserPunishment($row))
            ->all();

        $earnedSlugs = is_array($wallet['badges'] ?? null) ? $wallet['badges'] : [];
        $ownedPerks = is_array($wallet['perks'] ?? null) ? $wallet['perks'] : [];

        return [
            'has_wallet' => true,
            'wallet' => $wallet,
            'totals' => [
                'transactions' => $walletModel->transactions()->count(),
                'rewards' => $walletModel->transactions()
                    ->where(function ($query): void {
                        $query->where('points_delta', '>', 0)
                            ->orWhere('coins_delta', '>', 0);
                    })
                    ->count(),
                'penalties' => $walletModel->transactions()
                    ->where(function ($query): void {
                        $query->where('points_delta', '<', 0)
                            ->orWhere('coins_delta', '<', 0);
                    })
                    ->count(),
                'punishments_completed' => GamificationUserPunishment::query()
                    ->where('user_id', $user->id)
                    ->where('status', GamificationUserPunishmentStatus::Completed)
                    ->count(),
                'punishments_pending' => GamificationUserPunishment::query()
                    ->where('user_id', $user->id)
                    ->where('status', GamificationUserPunishmentStatus::Pending)
                    ->count(),
            ],
            'transactions' => $transactions,
            'punishments' => $punishments,
            'badges' => $this->formatBadges($earnedSlugs, $badgeCatalog),
            'perks' => $this->formatPerks($ownedPerks, $perkCatalog),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTransaction(GamificationTransaction $tx): array
    {
        $points = $tx->points_delta;
        $coins = $tx->coins_delta;
        $tone = ($points < 0 || $coins < 0) ? 'penalty' : (($points > 0 || $coins > 0 || $tx->xp_delta > 0) ? 'reward' : 'neutral');

        return [
            'id' => $tx->id,
            'event' => $tx->event,
            'event_label' => __('gamification.events.'.str_replace('.', '_', $tx->event), [], $tx->event),
            'rule_name' => $tx->rule?->name,
            'points_delta' => $points,
            'coins_delta' => $coins,
            'xp_delta' => $tx->xp_delta,
            'tone' => $tone,
            'created_at_human' => $tx->created_at?->diffForHumans(),
            'created_at_formatted' => $tx->created_at?->translatedFormat('M j, Y · H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserPunishment(GamificationUserPunishment $row): array
    {
        $punishment = $row->punishment;
        $meta = is_array($row->metadata) ? $row->metadata : [];

        return [
            'id' => $row->id,
            'status' => $row->status->value,
            'title' => $punishment?->title ?? '',
            'description' => $punishment?->description,
            'type' => $punishment?->type->value,
            'difficulty' => $punishment?->difficulty->value,
            'slip_trigger' => $row->slip_trigger,
            'assigned_at' => $row->assigned_at?->diffForHumans(),
            'completed_at' => $row->completed_at?->diffForHumans(),
            'points_recovered' => (int) ($meta['points_recovered'] ?? 0),
            'coins_recovered' => (int) ($meta['coins_recovered'] ?? 0),
        ];
    }

    /**
     * @param  list<string>  $earnedSlugs
     * @param  Collection<string, GamificationBadge>  $catalog
     * @return list<array<string, mixed>>
     */
    private function formatBadges(array $earnedSlugs, Collection $catalog): array
    {
        $items = [];

        foreach ($catalog as $badge) {
            $items[] = [
                'slug' => $badge->slug,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'earned' => in_array($badge->slug, $earnedSlugs, true),
            ];
        }

        usort($items, fn (array $a, array $b): int => ($b['earned'] <=> $a['earned']) ?: strcmp($a['name'], $b['name']));

        return $items;
    }

    /**
     * @param  list<string>  $ownedSlugs
     * @param  Collection<string, GamificationPerk>  $catalog
     * @return list<array<string, mixed>>
     */
    private function formatPerks(array $ownedSlugs, Collection $catalog): array
    {
        $items = [];

        foreach ($ownedSlugs as $slug) {
            $perk = $catalog->get($slug);
            $items[] = [
                'slug' => $slug,
                'name' => $perk?->name ?? $slug,
                'description' => $perk?->description,
                'type' => $perk?->type->value ?? 'permanent',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyState(): array
    {
        return [
            'has_wallet' => false,
            'wallet' => [],
            'totals' => [
                'transactions' => 0,
                'rewards' => 0,
                'penalties' => 0,
                'punishments_completed' => 0,
                'punishments_pending' => 0,
            ],
            'transactions' => [],
            'punishments' => [],
            'badges' => [],
            'perks' => [],
        ];
    }
}
