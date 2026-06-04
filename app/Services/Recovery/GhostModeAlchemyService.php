<?php

namespace App\Services\Recovery;

use Carbon\CarbonImmutable;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationWalletResolver;

/**
 * Positive Alchemy: pending commitment in wallet metadata + completion dispatch.
 */
readonly class GhostModeAlchemyService
{
    public function __construct(
        private GamificationWalletResolver $wallets,
        private GamificationEngine $gamification,
    ) {}

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string}  $actorContext
     */
    public function storePendingCommitment(
        array $actorContext,
        string $commitment,
        int $rewardCoins = 5,
        int $rewardXp = 10,
    ): void {
        $wallet = $this->wallets->resolve(
            $actorContext['user_id'] ?? null,
            $actorContext['guest_token'] ?? null,
        );

        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $meta['pending_alchemy'] = [
            'commitment' => $commitment,
            'expires_at' => CarbonImmutable::today()->endOfDay()->toIso8601String(),
            'reward_coins' => $rewardCoins,
            'reward_xp' => $rewardXp,
        ];
        $wallet->metadata = $meta;
        $wallet->save();
    }

    /**
     * @return array{commitment: string, expires_at: string}|null
     */
    public function pendingFor(?int $userId, ?string $guestToken): ?array
    {
        $wallet = $this->wallets->find($userId, $guestToken);

        if ($wallet === null) {
            return null;
        }

        $pending = is_array($wallet->metadata) ? ($wallet->metadata['pending_alchemy'] ?? null) : null;

        if (! is_array($pending) || ! isset($pending['commitment'])) {
            return null;
        }

        $expires = (string) ($pending['expires_at'] ?? '');
        if ($expires !== '' && CarbonImmutable::now()->gt(CarbonImmutable::parse($expires))) {
            $this->clearPending($wallet);

            return null;
        }

        return [
            'commitment' => (string) $pending['commitment'],
            'expires_at' => $expires,
        ];
    }

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string}  $actorContext
     * @return array<string, mixed>|null
     */
    public function completePending(array $actorContext): ?array
    {
        $wallet = $this->wallets->resolve(
            $actorContext['user_id'] ?? null,
            $actorContext['guest_token'] ?? null,
        );

        $pending = is_array($wallet->metadata) ? ($wallet->metadata['pending_alchemy'] ?? null) : null;

        if (! is_array($pending)) {
            return null;
        }

        $commitment = (string) ($pending['commitment'] ?? '');

        $result = $this->gamification->dispatch(
            GamificationEvent::AlchemyCommitmentCompleted->value,
            [
                'user_id' => $actorContext['user_id'] ?? null,
                'guest_token' => $actorContext['guest_token'] ?? null,
                'metadata' => [
                    'commitment_text' => $commitment,
                    'reward_coins' => (int) ($pending['reward_coins'] ?? 5),
                    'reward_xp' => (int) ($pending['reward_xp'] ?? 10),
                ],
            ],
        );

        $this->clearPending($wallet);

        return $result;
    }

    private function clearPending(GamificationWallet $wallet): void
    {
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        unset($meta['pending_alchemy']);
        $wallet->metadata = $meta;
        $wallet->save();
    }
}
