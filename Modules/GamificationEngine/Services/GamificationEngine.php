<?php

namespace Modules\GamificationEngine\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Enums\GamificationRuleType;
use Modules\GamificationEngine\Enums\GamificationShopEffectType;
use Modules\GamificationEngine\Http\Controllers\GamificationApiController;
use Modules\GamificationEngine\Models\GamificationRule;
use Modules\GamificationEngine\Models\GamificationShopItem;
use Modules\GamificationEngine\Models\GamificationTransaction;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Support\GamificationMetadataEnricher;

/**
 * Core gamification orchestrator: event dispatch, wallet mutations, shop, perks.
 *
 * Inject via constructor or app(GamificationEngine::class).
 * HTTP JSON alternative: {@see GamificationApiController}.
 *
 * Context array keys:
 * - user_id (int|null): authenticated user; defaults to auth()->id()
 * - guest_token (string|null): guest cookie egomap_guest when user_id is null
 * - metadata (array): matched against rule conditions (e.g. trigger => sent_message)
 * - reason (string): for adjustWallet admin notes
 */
class GamificationEngine
{
    public function __construct(
        private readonly GamificationWalletResolver $wallets,
        private readonly GamificationRuleMatcher $matcher,
        private readonly GamificationMetadataEnricher $metadataEnricher,
    ) {}

    /**
     * Fire an event: apply all matching active rules in priority order inside a DB transaction.
     *
     * @param  string  $event  One of {@see GamificationEvent} values (e.g. ghost_mode.daily_login).
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $context
     * @return array{event: string, applied: list<array>, points_delta: int, coins_delta: int, xp_delta: int, badges: list<string>, message: ?string, wallet: array}
     */
    public function dispatch(string $event, array $context = []): array
    {
        return DB::transaction(function () use ($event, $context): array {
            $wallet = $this->wallets->resolve(
                $context['user_id'] ?? auth()->id(),
                $context['guest_token'] ?? null,
            );

            $metadata = is_array($context['metadata'] ?? null) ? $context['metadata'] : [];
            $metadata = $this->metadataEnricher->enrichFromWallet($wallet, $event, $metadata);
            $rules = GamificationRule::query()
                ->where('event', $event)
                ->where('is_active', true)
                ->orderBy('priority')
                ->orderBy('id')
                ->get();

            $applied = [];
            $totals = ['points' => 0, 'coins' => 0, 'xp' => 0];
            $badges = [];
            $messages = [];

            foreach ($rules as $rule) {
                if (! $this->matcher->matches($rule, $metadata)) {
                    continue;
                }

                if ($this->exceedsDailyLimit($wallet, $rule)) {
                    continue;
                }

                $effects = is_array($rule->effects) ? $rule->effects : [];
                $deltas = $this->applyEffects($wallet, $rule, $effects, $event, $metadata);

                if ($deltas === null) {
                    continue;
                }

                $metadata = $this->metadataEnricher->enrichFromWallet($wallet, $event, $metadata);

                $totals['points'] += $deltas['points'];
                $totals['coins'] += $deltas['coins'];
                $totals['xp'] += $deltas['xp'];

                if ($deltas['badge'] !== null) {
                    $badges[] = $deltas['badge'];
                }

                $applied[] = [
                    'rule_key' => $rule->key,
                    'rule_name' => $rule->name,
                    'type' => $rule->rule_type->value,
                    ...$deltas,
                ];

                $messages[] = $rule->name;
            }

            $wallet->save();
            $this->recalculateLevel($wallet);

            if ($event === GamificationEvent::GhostModeDailyLogin->value && $applied !== []) {
                $wallet->update(['last_login_date' => CarbonImmutable::today()]);
            }

            return [
                'event' => $event,
                'applied' => $applied,
                'points_delta' => $totals['points'],
                'coins_delta' => $totals['coins'],
                'xp_delta' => $totals['xp'],
                'badges' => array_values(array_unique($badges)),
                'message' => $messages !== [] ? implode(' · ', $messages) : null,
                'wallet' => $this->walletSnapshot($wallet),
            ];
        });
    }

    /**
     * Dry-run: which rules would match without creating a wallet or writing transactions.
     *
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $context
     * @return list<array{key: string, name: string, type: string, effects: array}>
     */
    public function preview(string $event, array $context = []): array
    {
        $metadata = is_array($context['metadata'] ?? null) ? $context['metadata'] : [];

        return GamificationRule::query()
            ->where('event', $event)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get()
            ->filter(fn (GamificationRule $rule): bool => $this->matcher->matches($rule, $metadata))
            ->map(fn (GamificationRule $rule): array => [
                'key' => $rule->key,
                'name' => $rule->name,
                'type' => $rule->rule_type->value,
                'effects' => $rule->effects,
            ])
            ->values()
            ->all();
    }

    /**
     * Read wallet snapshot; creates wallet on first access via {@see GamificationWalletResolver}.
     *
     * @return array{points: int, coins: int, xp: int, level: int, streak_days: int, badges: list<string>, perks: list<string>, metadata: array, xp_progress: int, xp_needed: int, xp_percent: int, last_login_date: ?string}
     */
    public function walletFor(?User $user = null, ?string $guestToken = null): array
    {
        $wallet = $this->wallets->resolve($user?->id ?? auth()->id(), $guestToken);

        return $this->walletSnapshot($wallet);
    }

    /**
     * Recent ledger entries for the actor (empty if wallet does not exist yet).
     *
     * @return list<array{event: string, points_delta: int, coins_delta: int, xp_delta: int, metadata: mixed, created_at: ?string}>
     */
    public function recentTransactions(?User $user = null, int $limit = 10): array
    {
        $wallet = $this->wallets->find($user?->id ?? auth()->id(), null);

        if ($wallet === null) {
            return [];
        }

        return $wallet->transactions()
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (GamificationTransaction $tx): array => [
                'id' => $tx->id,
                'event' => $tx->event,
                'event_label' => __('gamification.events.'.str_replace('.', '_', $tx->event), [], $tx->event),
                'points_delta' => $tx->points_delta,
                'coins_delta' => $tx->coins_delta,
                'xp_delta' => $tx->xp_delta,
                'metadata' => $tx->metadata,
                'created_at' => $tx->created_at?->toIso8601String(),
                'created_at_human' => $tx->created_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Merge guest wallet into user on signup/login; returns number of rows updated (0 or 1).
     */
    public function claimForUser(User $user, ?string $guestToken = null): int
    {
        $guestToken ??= request()->cookie('egomap_guest');

        if ($guestToken === null || $guestToken === '') {
            return 0;
        }

        return GamificationWallet::query()
            ->whereNull('user_id')
            ->where('guest_token', $guestToken)
            ->update(['user_id' => $user->id, 'guest_token' => null]);
    }

    /**
     * Use a consumable perk: removes slug from wallet.perks and logs perk.consumed.
     *
     * @param  array{user_id?: ?int, guest_token?: ?string}  $context
     * @return array{success: bool, message?: string, perk?: string, wallet: array}
     */
    public function consumePerk(string $perkSlug, array $context = []): array
    {
        return DB::transaction(function () use ($perkSlug, $context): array {
            $wallet = $this->wallets->resolve(
                $context['user_id'] ?? auth()->id(),
                $context['guest_token'] ?? null,
            );

            $perks = is_array($wallet->perks) ? $wallet->perks : [];

            if (! in_array($perkSlug, $perks, true)) {
                return [
                    'success' => false,
                    'message' => __('gamification.perks.not_owned'),
                    'wallet' => $this->walletSnapshot($wallet),
                ];
            }

            $wallet->perks = array_values(array_filter($perks, fn (string $slug): bool => $slug !== $perkSlug));
            $wallet->save();

            GamificationTransaction::query()->create([
                'gamification_wallet_id' => $wallet->id,
                'event' => GamificationEvent::PerkConsumed->value,
                'points_delta' => 0,
                'coins_delta' => 0,
                'xp_delta' => 0,
                'metadata' => ['perk' => $perkSlug],
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'perk' => $perkSlug,
                'wallet' => $this->walletSnapshot($wallet),
            ];
        });
    }

    /**
     * Buy a shop item with coins; applies effect_type (freeze, shield, boost, grant perk).
     *
     * @param  array{user_id?: ?int, guest_token?: ?string}  $context
     * @return array{success: bool, message?: string, item?: string, item_name?: string, external_action?: mixed, wallet?: array}
     */
    public function purchaseShopItem(string $itemSlug, array $context = []): array
    {
        return DB::transaction(function () use ($itemSlug, $context): array {
            $item = GamificationShopItem::query()
                ->where('slug', $itemSlug)
                ->where('is_active', true)
                ->first();

            if ($item === null) {
                return ['success' => false, 'message' => __('gamification.shop.item_not_found')];
            }

            $wallet = $this->wallets->resolve(
                $context['user_id'] ?? auth()->id(),
                $context['guest_token'] ?? null,
            );

            if ($wallet->coins < $item->cost_coins) {
                return [
                    'success' => false,
                    'message' => __('gamification.shop.insufficient_coins'),
                    'wallet' => $this->walletSnapshot($wallet),
                ];
            }

            $wallet->coins -= $item->cost_coins;
            $effects = is_array($item->effects) ? $item->effects : [];
            $externalAction = $this->applyShopEffect($wallet, $item->effect_type, $effects);

            $wallet->save();
            $this->recalculateLevel($wallet);

            GamificationTransaction::query()->create([
                'gamification_wallet_id' => $wallet->id,
                'event' => GamificationEvent::ShopPurchase->value,
                'points_delta' => 0,
                'coins_delta' => -$item->cost_coins,
                'xp_delta' => 0,
                'metadata' => [
                    'item_slug' => $item->slug,
                    'item_name' => $item->name,
                    'effect_type' => $item->effect_type->value,
                ],
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'item' => $item->slug,
                'item_name' => $item->name,
                'external_action' => $externalAction,
                'wallet' => $this->walletSnapshot($wallet),
            ];
        });
    }

    /**
     * Manual admin correction; logs admin.adjustment (super-admin wallets UI).
     *
     * @param  array{user_id?: ?int, guest_token?: ?string, reason?: string}  $context
     * @return array{wallet: array}
     */
    public function adjustWallet(int $pointsDelta, int $coinsDelta, int $xpDelta, array $context = []): array
    {
        return DB::transaction(function () use ($pointsDelta, $coinsDelta, $xpDelta, $context): array {
            $wallet = $this->wallets->resolve(
                $context['user_id'] ?? auth()->id(),
                $context['guest_token'] ?? null,
            );

            $wallet->points = max(0, $wallet->points + $pointsDelta);
            $wallet->coins = max(0, $wallet->coins + $coinsDelta);
            $wallet->xp = max(0, $wallet->xp + $xpDelta);
            $wallet->save();
            $this->recalculateLevel($wallet);

            GamificationTransaction::query()->create([
                'gamification_wallet_id' => $wallet->id,
                'event' => GamificationEvent::AdminAdjustment->value,
                'points_delta' => $pointsDelta,
                'coins_delta' => $coinsDelta,
                'xp_delta' => $xpDelta,
                'metadata' => ['reason' => $context['reason'] ?? null],
                'created_at' => now(),
            ]);

            return ['wallet' => $this->walletSnapshot($wallet)];
        });
    }

    /**
     * Admin simulator payload: event + matching rules (same as preview, different shape).
     *
     * @param  array{metadata?: array<string, mixed>}  $context
     * @return array{event: string, matches: list<array>}
     */
    public function simulate(string $event, array $context = []): array
    {
        return [
            'event' => $event,
            'matches' => $this->preview($event, $context),
        ];
    }

    /**
     * Active Phoenix Shop catalogue for UI and GET /api/v1/gamification/shop.
     *
     * @return list<array{slug: string, name: string, description: ?string, icon: string, cost_coins: int, effect_type: string, effects: array}>
     */
    public function activeShopItems(): array
    {
        return GamificationShopItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (GamificationShopItem $item): array => [
                'slug' => $item->slug,
                'name' => $item->name,
                'description' => $item->description,
                'icon' => $item->icon,
                'cost_coins' => $item->cost_coins,
                'effect_type' => $item->effect_type->value,
                'effects' => $item->effects,
            ])
            ->all();
    }

    /**
     * Sum reward/penalty deltas for slip UI before user confirms (no wallet write).
     *
     * @param  array{metadata?: array<string, mixed>}  $context
     * @return array{points: int, coins: int, xp: int}
     */
    public function previewSlipNet(array $context): array
    {
        $matches = $this->preview(GamificationEvent::GhostModeSlipReported->value, $context);
        $totals = ['points' => 0, 'coins' => 0, 'xp' => 0];

        foreach ($matches as $rule) {
            $effects = is_array($rule['effects'] ?? null) ? $rule['effects'] : [];
            $totals['points'] += (int) ($effects['points'] ?? 0);
            $totals['coins'] += (int) ($effects['coins'] ?? 0);
            $totals['xp'] += (int) ($effects['xp'] ?? 0);
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $effects
     * @return array{points: int, coins: int, xp: int, badge: ?string}|null
     */
    private function applyEffects(
        GamificationWallet $wallet,
        GamificationRule $rule,
        array $effects,
        string $event,
        array $metadata,
    ): ?array {
        $points = (int) ($effects['points'] ?? 0);
        $coins = (int) ($effects['coins'] ?? 0);
        $xp = (int) ($effects['xp'] ?? 0);

        if (isset($effects['points_from_metadata']) && is_string($effects['points_from_metadata'])) {
            $points = max(0, (int) ($metadata[$effects['points_from_metadata']] ?? 0));
        }

        if (isset($effects['coins_from_metadata']) && is_string($effects['coins_from_metadata'])) {
            $coins = max(0, (int) ($metadata[$effects['coins_from_metadata']] ?? 0));
        }
        $badge = isset($effects['badge']) && is_string($effects['badge']) ? $effects['badge'] : null;
        $perk = isset($effects['perk']) && is_string($effects['perk']) ? $effects['perk'] : null;

        $hasMetaOnly = isset($effects['metadata']) || isset($effects['increment_metadata'])
            || isset($effects['streak_freeze_hours'])
            || isset($effects['points_from_metadata'])
            || isset($effects['coins_from_metadata']);

        if ($points === 0 && $coins === 0 && $xp === 0 && $badge === null && $perk === null
            && ! ($effects['reset_streak'] ?? false)
            && ! ($effects['increment_streak'] ?? false)
            && ! $hasMetaOnly) {
            return null;
        }

        if ($rule->rule_type === GamificationRuleType::Penalty) {
            $wallet->points = max(0, $wallet->points + $points);
            $wallet->coins = max(0, $wallet->coins + $coins);
            $wallet->xp = max(0, $wallet->xp + $xp);
        } else {
            $wallet->points += $points;
            $wallet->coins = max(0, $wallet->coins + $coins);
            $wallet->xp = max(0, $wallet->xp + $xp);
        }

        if ($effects['reset_streak'] ?? false) {
            $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
            $freezeUntil = $meta['streak_freeze_until'] ?? null;
            $freezeActive = is_string($freezeUntil)
                && $freezeUntil !== ''
                && CarbonImmutable::now()->lt(CarbonImmutable::parse($freezeUntil));

            if ($freezeActive) {
                $wallet->metadata = $meta;
            } elseif ((int) ($meta['streak_freeze_charges'] ?? 0) > 0) {
                $meta['streak_freeze_charges'] = (int) $meta['streak_freeze_charges'] - 1;
                $wallet->metadata = $meta;
            } else {
                $wallet->streak_days = 0;
            }
        }

        if ($effects['increment_streak'] ?? false) {
            $wallet->streak_days++;
        }

        if ($badge !== null && ! $wallet->hasBadge($badge)) {
            $badges = $wallet->badgeSlugs();
            $badges[] = $badge;
            $wallet->badges = array_values(array_unique($badges));
        }

        if ($perk !== null) {
            $perks = is_array($wallet->perks) ? $wallet->perks : [];
            if (! in_array($perk, $perks, true)) {
                $perks[] = $perk;
                $wallet->perks = array_values($perks);
            }
        }

        if (isset($effects['increment_metadata']) && is_array($effects['increment_metadata'])) {
            $walletMeta = is_array($wallet->metadata) ? $wallet->metadata : [];
            foreach ($effects['increment_metadata'] as $metaKey => $delta) {
                if (! is_string($metaKey)) {
                    continue;
                }

                $walletMeta[$metaKey] = (int) ($walletMeta[$metaKey] ?? 0) + (int) $delta;
            }

            $wallet->metadata = $walletMeta;
        }

        if (isset($effects['streak_freeze_hours'])) {
            $walletMeta = is_array($wallet->metadata) ? $wallet->metadata : [];
            $hours = max(1, (int) $effects['streak_freeze_hours']);
            $walletMeta['streak_freeze_until'] = CarbonImmutable::now()->addHours($hours)->toIso8601String();
            $wallet->metadata = $walletMeta;
        }

        if (isset($effects['metadata']) && is_array($effects['metadata'])) {
            $walletMeta = is_array($wallet->metadata) ? $wallet->metadata : [];
            $wallet->metadata = array_merge($walletMeta, $effects['metadata']);
        }

        GamificationTransaction::query()->create([
            'gamification_wallet_id' => $wallet->id,
            'gamification_rule_id' => $rule->id,
            'event' => $event,
            'points_delta' => $points,
            'coins_delta' => $coins,
            'xp_delta' => $xp,
            'metadata' => array_merge($metadata, ['rule_key' => $rule->key]),
            'created_at' => now(),
        ]);

        return [
            'points' => $points,
            'coins' => $coins,
            'xp' => $xp,
            'badge' => $badge,
        ];
    }

    private function exceedsDailyLimit(GamificationWallet $wallet, GamificationRule $rule): bool
    {
        if ($rule->max_per_day === null || $rule->max_per_day < 1) {
            return false;
        }

        $todayCount = GamificationTransaction::query()
            ->where('gamification_wallet_id', $wallet->id)
            ->where('gamification_rule_id', $rule->id)
            ->whereDate('created_at', CarbonImmutable::today())
            ->count();

        return $todayCount >= $rule->max_per_day;
    }

    private function recalculateLevel(GamificationWallet $wallet): void
    {
        $perLevel = max(1, (int) config('gamification.xp_per_level', 100));
        $maxLevel = max(1, (int) config('gamification.max_level', 100));
        $wallet->level = min($maxLevel, max(1, 1 + intdiv($wallet->xp, $perLevel)));
        $wallet->save();
    }

    /**
     * @param  array<string, mixed>  $effects
     * @return array<string, mixed>|null
     */
    private function applyShopEffect(
        GamificationWallet $wallet,
        GamificationShopEffectType $effectType,
        array $effects,
    ): ?array {
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];

        return match ($effectType) {
            GamificationShopEffectType::StreakFreeze => tap(null, function () use (&$meta, $effects, $wallet): void {
                $charges = (int) ($effects['charges'] ?? 1);
                $meta['streak_freeze_charges'] = (int) ($meta['streak_freeze_charges'] ?? 0) + $charges;
                $wallet->metadata = $meta;
            }),
            GamificationShopEffectType::ShieldRepair => [
                'type' => 'shield_repair',
                'percent' => (int) ($effects['percent'] ?? 10),
            ],
            GamificationShopEffectType::EmergencyBoost => tap(null, function () use (&$meta, $effects, $wallet): void {
                $hours = (int) ($effects['hours'] ?? 12);
                $meta['emergency_boost_until'] = now()->addHours($hours)->toIso8601String();
                $wallet->metadata = $meta;
            }),
            GamificationShopEffectType::GrantPerk => tap(null, function () use ($effects, $wallet): void {
                $perk = (string) ($effects['perk'] ?? '');
                if ($perk === '') {
                    return;
                }

                $perks = is_array($wallet->perks) ? $wallet->perks : [];
                if (! in_array($perk, $perks, true)) {
                    $perks[] = $perk;
                    $wallet->perks = array_values($perks);
                }
            }),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function walletSnapshot(GamificationWallet $wallet): array
    {
        $perLevel = max(1, (int) config('gamification.xp_per_level', 100));
        $xpProgress = $wallet->xp % $perLevel;

        return [
            'points' => $wallet->points,
            'coins' => $wallet->coins,
            'xp' => $wallet->xp,
            'level' => $wallet->level,
            'streak_days' => $wallet->streak_days,
            'badges' => $wallet->badgeSlugs(),
            'perks' => is_array($wallet->perks) ? $wallet->perks : [],
            'metadata' => is_array($wallet->metadata) ? $wallet->metadata : [],
            'xp_progress' => $xpProgress,
            'xp_needed' => $perLevel,
            'xp_percent' => (int) round(($xpProgress / $perLevel) * 100),
            'last_login_date' => $wallet->last_login_date?->toDateString(),
        ];
    }
}
