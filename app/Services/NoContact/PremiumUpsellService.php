<?php

namespace App\Services\NoContact;

use App\Models\NoContactProtocol;
use App\Models\User;
use App\Support\LocaleConfig;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationWalletResolver;

/**
 * Smart Pro upsell on Ghost Mode day 4+ (configurable), with deferral and per-streak reset.
 */
readonly class PremiumUpsellService
{
    private const MetaKey = 'premium_upsell';

    public function __construct(
        private GamificationWalletResolver $wallets,
    ) {}

    /**
     * @return array{
     *     show: bool,
     *     variant: string,
     *     discount_percent: int,
     *     coupon: string,
     *     price_display: string,
     *     checkout_url: string,
     *     clean_streak_days: int
     * }|null
     */
    public function resolve(?User $user, ?NoContactProtocol $protocol, int $cleanStreakDays): ?array
    {
        if ($user === null || $protocol === null || $user->isPro()) {
            return null;
        }

        $minStreak = max(0, (int) config('gamification.premium_upsell.min_clean_streak_days', 3));

        if ($cleanStreakDays < $minStreak) {
            return null;
        }

        $wallet = $this->wallets->find($user->id, null);

        if ($wallet === null) {
            return null;
        }

        $state = $this->normalizedState($wallet->metadata[self::MetaKey] ?? null, $protocol);

        if ($state['dismiss_count'] >= 2) {
            return null;
        }

        if ($state['deferred_until'] !== null && CarbonImmutable::now()->lt($state['deferred_until'])) {
            return null;
        }

        $variant = $state['dismiss_count'] === 0 ? 'first' : 'reminder';
        $discountPercent = $variant === 'first'
            ? (int) config('gamification.premium_upsell.first_discount_percent', 40)
            : (int) config('gamification.premium_upsell.reminder_discount_percent', 25);

        $coupon = $variant === 'first'
            ? (string) config('gamification.premium_upsell.coupon_first', 'UPSELL40')
            : (string) config('gamification.premium_upsell.coupon_reminder', 'UPSELL25');

        $basePrice = (float) config('gamification.premium_upsell.base_price_usd', 15);
        $salePrice = round($basePrice * (1 - ($discountPercent / 100)), 2);

        return [
            'show' => true,
            'variant' => $variant,
            'discount_percent' => $discountPercent,
            'coupon' => $coupon,
            'price_display' => '$'.number_format($salePrice, $salePrice === floor($salePrice) ? 0 : 2),
            'checkout_url' => $this->checkoutUrl($coupon),
            'clean_streak_days' => $cleanStreakDays,
        ];
    }

    public function defer(User $user, ?NoContactProtocol $protocol): void
    {
        $wallet = $this->wallets->resolve($user->id, null);
        $state = $this->normalizedState($wallet->metadata[self::MetaKey] ?? null, $protocol);
        $deferDays = max(1, (int) config('gamification.premium_upsell.defer_days', 3));

        $state['dismiss_count'] = min(2, $state['dismiss_count'] + 1);
        $state['deferred_until'] = CarbonImmutable::now()->addDays($deferDays);

        $this->persistState($wallet, $state);
        $this->syncUserDeferColumns($user, $state['deferred_until'], $state['dismiss_count']);
    }

    public function markCheckoutClicked(User $user, ?NoContactProtocol $protocol): void
    {
        $wallet = $this->wallets->resolve($user->id, null);
        $state = $this->normalizedState($wallet->metadata[self::MetaKey] ?? null, $protocol);
        $state['dismiss_count'] = 2;

        $this->persistState($wallet, $state);
        $this->syncUserDeferColumns($user, null, 2);
    }

    public function resetForNewStreak(User $user): void
    {
        $wallet = $this->wallets->find($user->id, null);

        if ($wallet === null) {
            return;
        }

        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        unset($meta[self::MetaKey]);
        $wallet->metadata = $meta;
        $wallet->save();

        $user->forceFill([
            'premium_upsell_deferred_at' => null,
            'premium_upsell_dismiss_count' => 0,
        ])->save();
    }

    /**
     * @return array{protocol_uuid: string, deferred_until: ?CarbonImmutable, dismiss_count: int}
     */
    private function normalizedState(mixed $raw, NoContactProtocol $protocol): array
    {
        $state = is_array($raw) ? $raw : [];
        $deferredRaw = $state['deferred_until'] ?? null;
        $deferred = is_string($deferredRaw) && $deferredRaw !== ''
            ? CarbonImmutable::parse($deferredRaw)
            : null;

        if (($state['protocol_uuid'] ?? null) !== $protocol->uuid) {
            return [
                'protocol_uuid' => $protocol->uuid,
                'deferred_until' => null,
                'dismiss_count' => 0,
            ];
        }

        return [
            'protocol_uuid' => $protocol->uuid,
            'deferred_until' => $deferred,
            'dismiss_count' => max(0, (int) ($state['dismiss_count'] ?? 0)),
        ];
    }

    /**
     * @param  array{protocol_uuid: string, deferred_until: ?CarbonImmutable, dismiss_count: int}  $state
     */
    private function persistState(GamificationWallet $wallet, array $state): void
    {
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $meta[self::MetaKey] = [
            'protocol_uuid' => $state['protocol_uuid'],
            'deferred_until' => $state['deferred_until']?->toIso8601String(),
            'dismiss_count' => $state['dismiss_count'],
        ];
        $wallet->metadata = $meta;
        $wallet->save();
    }

    private function syncUserDeferColumns(User $user, ?CarbonImmutable $deferredUntil, int $dismissCount): void
    {
        $user->forceFill([
            'premium_upsell_deferred_at' => $deferredUntil,
            'premium_upsell_dismiss_count' => $dismissCount,
        ])->save();
    }

    private function checkoutUrl(string $coupon): string
    {
        return URL::to(route('pricing', LocaleConfig::routeParameters())).'?'.http_build_query([
            'coupon' => $coupon,
            'upgrade' => 'pro',
        ]);
    }
}
