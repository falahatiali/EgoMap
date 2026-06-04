<?php

namespace Modules\GamificationEngine\Services;

use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Enums\GamificationRuleType;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationPerk;
use Modules\GamificationEngine\Models\GamificationRule;
use Modules\GamificationEngine\Models\GamificationShopItem;
use Modules\GamificationEngine\Support\GamificationEffectSummary;

/**
 * Read-only aggregates for admin catalogue, ideation, and API reference screens.
 */
class GamificationCatalogService
{
    /**
     * High-level counts for rewards vs penalties and related catalogues.
     *
     * @return array{
     *     rewards_count: int,
     *     penalties_count: int,
     *     active_rules: int,
     *     badges: int,
     *     perks: int,
     *     shop_items: int,
     *     events_with_rules: int
     * }
     */
    public function overview(): array
    {
        $rewards = GamificationRule::query()->where('rule_type', GamificationRuleType::Reward)->where('is_active', true)->count();
        $penalties = GamificationRule::query()->where('rule_type', GamificationRuleType::Penalty)->where('is_active', true)->count();

        return [
            'rewards_count' => $rewards,
            'penalties_count' => $penalties,
            'active_rules' => $rewards + $penalties,
            'badges' => GamificationBadge::query()->where('is_active', true)->count(),
            'perks' => GamificationPerk::query()->where('is_active', true)->count(),
            'shop_items' => GamificationShopItem::query()->where('is_active', true)->count(),
            'events_with_rules' => GamificationRule::query()->where('is_active', true)->distinct('event')->count('event'),
        ];
    }

    /**
     * Active reward rules with effect summaries.
     *
     * @return list<array<string, mixed>>
     */
    public function rewards(): array
    {
        return $this->rulesByType(GamificationRuleType::Reward);
    }

    /**
     * Active penalty rules with effect summaries.
     *
     * @return list<array<string, mixed>>
     */
    public function penalties(): array
    {
        return $this->rulesByType(GamificationRuleType::Penalty);
    }

    /**
     * Every known event with its active rules (for ideation / wiring new features).
     *
     * @return list<array{event: string, rules: list<array<string, mixed>>}>
     */
    public function eventsMap(): array
    {
        $rules = GamificationRule::query()
            ->where('is_active', true)
            ->orderBy('event')
            ->orderBy('priority')
            ->get();

        $grouped = [];

        foreach (GamificationEvent::cases() as $eventCase) {
            $grouped[$eventCase->value] = [];
        }

        foreach ($rules as $rule) {
            $grouped[$rule->event][] = $this->ruleRow($rule);
        }

        $result = [];
        foreach ($grouped as $event => $eventRules) {
            $result[] = [
                'event' => $event,
                'rules' => $eventRules,
            ];
        }

        return $result;
    }

    /**
     * Documented PHP service methods and optional HTTP routes (for integrators).
     *
     * @return list<array{method: string, signature: string, description: string, http?: string}>
     */
    public function serviceApiReference(): array
    {
        return [
            [
                'method' => 'dispatch',
                'signature' => 'dispatch(string $event, array $context = []): array',
                'description' => 'Apply all matching active rules for an event; updates wallet and writes transactions.',
                'http' => 'POST /api/v1/gamification/dispatch',
            ],
            [
                'method' => 'preview',
                'signature' => 'preview(string $event, array $context = []): array',
                'description' => 'List rules that would match without mutating the wallet.',
                'http' => 'POST /api/v1/gamification/preview',
            ],
            [
                'method' => 'simulate',
                'signature' => 'simulate(string $event, array $context = []): array',
                'description' => 'Alias-style preview payload for admin simulator.',
                'http' => null,
            ],
            [
                'method' => 'walletFor',
                'signature' => 'walletFor(?User $user = null, ?string $guestToken = null): array',
                'description' => 'Current wallet snapshot (points, coins, xp, level, badges, perks, metadata).',
                'http' => 'GET /api/v1/gamification/wallet',
            ],
            [
                'method' => 'recentTransactions',
                'signature' => 'recentTransactions(?User $user = null, int $limit = 10): array',
                'description' => 'Latest ledger rows for the actor wallet.',
                'http' => 'GET /api/v1/gamification/transactions',
            ],
            [
                'method' => 'purchaseShopItem',
                'signature' => 'purchaseShopItem(string $itemSlug, array $context = []): array',
                'description' => 'Spend coins and apply shop effect (freeze, shield, boost, perk).',
                'http' => 'POST /api/v1/gamification/shop/{slug}/purchase',
            ],
            [
                'method' => 'consumePerk',
                'signature' => 'consumePerk(string $perkSlug, array $context = []): array',
                'description' => 'Remove a consumable perk from wallet after use.',
                'http' => 'POST /api/v1/gamification/perks/{slug}/consume',
            ],
            [
                'method' => 'activeShopItems',
                'signature' => 'activeShopItems(): array',
                'description' => 'Catalogue of purchasable shop rows.',
                'http' => 'GET /api/v1/gamification/shop',
            ],
            [
                'method' => 'claimForUser',
                'signature' => 'claimForUser(User $user, ?string $guestToken = null): int',
                'description' => 'Attach guest wallet to user on registration (cookie egomap_guest).',
                'http' => null,
            ],
            [
                'method' => 'adjustWallet',
                'signature' => 'adjustWallet(int $pointsDelta, int $coinsDelta, int $xpDelta, array $context = []): array',
                'description' => 'Super-admin manual correction; logs admin.adjustment event.',
                'http' => null,
            ],
            [
                'method' => 'previewSlipNet',
                'signature' => 'previewSlipNet(array $context): array',
                'description' => 'Sum of points/coins/xp from slip rules for UI warnings.',
                'http' => null,
            ],
        ];
    }

    /**
     * Supported keys inside rule effects JSON.
     *
     * @return list<array{key: string, type: string, description: string}>
     */
    public function effectFieldReference(): array
    {
        return [
            ['key' => 'points', 'type' => 'int', 'description' => 'Wallet points delta (penalties floor at 0).'],
            ['key' => 'coins', 'type' => 'int', 'description' => 'Phoenix coins delta.'],
            ['key' => 'xp', 'type' => 'int', 'description' => 'Experience; level = 1 + floor(xp / xp_per_level).'],
            ['key' => 'badge', 'type' => 'string slug', 'description' => 'Grant badge if not already owned.'],
            ['key' => 'perk', 'type' => 'string slug', 'description' => 'Append perk slug to wallet.perks.'],
            ['key' => 'increment_streak', 'type' => 'bool', 'description' => 'Increment streak_days by 1.'],
            ['key' => 'reset_streak', 'type' => 'bool', 'description' => 'Zero streak unless streak_freeze_charges in metadata.'],
            ['key' => 'metadata', 'type' => 'object', 'description' => 'Merged into wallet.metadata (e.g. freeze charges).'],
            ['key' => 'increment_metadata', 'type' => 'object', 'description' => 'Increment numeric keys in wallet.metadata (e.g. streak_freeze_charges).'],
            ['key' => 'streak_freeze_hours', 'type' => 'int', 'description' => 'Set wallet.metadata.streak_freeze_until for N hours (slip streak protection).'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rulesByType(GamificationRuleType $type): array
    {
        return GamificationRule::query()
            ->where('rule_type', $type)
            ->orderBy('event')
            ->orderBy('priority')
            ->get()
            ->map(fn (GamificationRule $rule): array => $this->ruleRow($rule))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleRow(GamificationRule $rule): array
    {
        return [
            'id' => $rule->id,
            'key' => $rule->key,
            'name' => $rule->name,
            'event' => $rule->event,
            'type' => $rule->rule_type->value,
            'priority' => $rule->priority,
            'max_per_day' => $rule->max_per_day,
            'is_active' => $rule->is_active,
            'conditions' => $rule->conditions,
            'effects' => $rule->effects,
            'effects_summary' => GamificationEffectSummary::fromEffects($rule->effects),
            'edit_url' => route('admin.gamification.rules.edit', $rule),
        ];
    }
}
