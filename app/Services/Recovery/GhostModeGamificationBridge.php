<?php

namespace App\Services\Recovery;

use App\Enums\GhostModeEventType;
use App\Models\GhostModeEvent;
use App\Models\NoContactProtocol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationWalletResolver;

/**
 * Connects Ghost Mode UX actions to GamificationEngine events and wallet metadata progress.
 */
readonly class GhostModeGamificationBridge
{
    public function __construct(
        private GamificationEngine $gamification,
        private GamificationWalletResolver $wallets,
    ) {}

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $actorContext
     * @return array<string, mixed>
     */
    public function emergencyMetadata(?NoContactProtocol $protocol, array $actorContext): array
    {
        $count = 0;

        if ($protocol !== null) {
            $count = GhostModeEvent::query()
                ->where('no_contact_protocol_id', $protocol->id)
                ->where('type', GhostModeEventType::Emergency)
                ->count();
        }

        return array_merge($actorContext['metadata'] ?? [], [
            'emergency_count' => $count + 1,
        ]);
    }

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $actorContext
     * @return array<string, mixed>
     */
    public function dispatchEmergencyCompleted(array $actorContext, ?NoContactProtocol $protocol): array
    {
        $metadata = $this->emergencyMetadata($protocol, $actorContext);

        return $this->gamification->dispatch(
            GamificationEvent::GhostModeEmergencyCompleted->value,
            [
                'user_id' => $actorContext['user_id'] ?? null,
                'guest_token' => $actorContext['guest_token'] ?? null,
                'metadata' => $metadata,
            ],
        );
    }

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $actorContext
     * @return array<string, mixed>
     */
    public function dispatchPanicChallenge(
        array $actorContext,
        ?NoContactProtocol $protocol,
        bool $breathingSuccess,
        bool $challengeCompleted,
    ): array {
        $metadata = $this->emergencyMetadata($protocol, $actorContext);

        return $this->gamification->dispatch(
            GamificationEvent::PanicChallengeCompleted->value,
            [
                'user_id' => $actorContext['user_id'] ?? null,
                'guest_token' => $actorContext['guest_token'] ?? null,
                'metadata' => array_merge($metadata, [
                    'success' => true,
                    'breathing' => $breathingSuccess,
                    'challenge' => $challengeCompleted,
                ]),
            ],
        );
    }

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $actorContext
     * @return array<string, mixed>
     */
    public function afterBlackholeAnalyzed(array $actorContext, int $regretProbability): array
    {
        $highRisk = $regretProbability >= 70 ? 1 : 0;

        return $this->gamification->dispatch(
            GamificationEvent::BlackholeAnalyzed->value,
            [
                'user_id' => $actorContext['user_id'] ?? null,
                'guest_token' => $actorContext['guest_token'] ?? null,
                'metadata' => array_merge($actorContext['metadata'] ?? [], [
                    'high_risk' => $highRisk,
                    'regret_probability' => $regretProbability,
                ]),
            ],
        );
    }

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $actorContext
     * @return list<array<string, mixed>>
     */
    public function afterBlackholeDestroyed(array $actorContext, bool $destroyedRewrite): array
    {
        $wallet = $this->wallets->resolve(
            $actorContext['user_id'] ?? null,
            $actorContext['guest_token'] ?? null,
        );

        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $previousTier = (int) ($meta['blackhole_tier'] ?? 0);
        $writesTotal = (int) ($meta['blackhole_writes_total'] ?? 0) + 1;
        $newTier = min(20, intdiv($writesTotal, 5));
        $today = CarbonImmutable::today()->toDateString();
        $lastDate = isset($meta['last_blackhole_date']) ? (string) $meta['last_blackhole_date'] : null;
        $streak = (int) ($meta['blackhole_streak_days'] ?? 0);

        if ($lastDate === $today) {
            // same day, streak unchanged
        } elseif ($lastDate === CarbonImmutable::yesterday()->toDateString()) {
            $streak++;
        } else {
            $streak = 1;
        }

        $meta['blackhole_writes_total'] = $writesTotal;
        $meta['blackhole_tier'] = $newTier;
        $meta['last_blackhole_date'] = $today;
        $meta['blackhole_streak_days'] = $streak;
        $wallet->metadata = $meta;
        $wallet->save();

        $results = [];

        $writeContext = [
            'user_id' => $actorContext['user_id'] ?? null,
            'guest_token' => $actorContext['guest_token'] ?? null,
            'metadata' => array_merge($actorContext['metadata'] ?? [], [
                'writes_total' => $writesTotal,
                'writes_today' => $this->writesTodayCount($wallet),
            ]),
        ];

        $results[] = $this->gamification->dispatch(
            GamificationEvent::GhostModeBlackholeWrite->value,
            $writeContext,
        );

        if ($destroyedRewrite) {
            $results[] = $this->gamification->dispatch(
                GamificationEvent::BlackholeRewriteAccepted->value,
                $writeContext,
            );
        }

        if ($newTier > $previousTier) {
            $results[] = $this->gamification->dispatch(
                GamificationEvent::BlackholeTierReached->value,
                array_merge($writeContext, ['metadata' => ['tier' => $newTier]]),
            );
        }

        if ($streak === 7) {
            $results[] = $this->gamification->dispatch(
                GamificationEvent::BlackholeStreak7->value,
                array_merge($writeContext, ['metadata' => ['blackhole_streak_days' => 7]]),
            );
        }

        return $results;
    }

    /**
     * @return array{writes_total: int, tier: int, tier_progress: int, streak_days: int}
     */
    public function blackholeProgress(?User $user, ?string $guestToken): array
    {
        $wallet = $this->wallets->find($user?->id, $guestToken);

        if ($wallet === null) {
            return [
                'writes_total' => 0,
                'tier' => 0,
                'tier_progress' => 0,
                'streak_days' => 0,
            ];
        }

        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $writesTotal = (int) ($meta['blackhole_writes_total'] ?? 0);
        $tier = (int) ($meta['blackhole_tier'] ?? 0);

        return [
            'writes_total' => $writesTotal,
            'tier' => $tier,
            'tier_progress' => $writesTotal % 5,
            'streak_days' => (int) ($meta['blackhole_streak_days'] ?? 0),
        ];
    }

    private function writesTodayCount(GamificationWallet $wallet): int
    {
        return $wallet->transactions()
            ->where('event', GamificationEvent::GhostModeBlackholeWrite->value)
            ->whereDate('created_at', CarbonImmutable::today())
            ->count();
    }
}
