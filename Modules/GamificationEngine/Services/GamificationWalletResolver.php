<?php

namespace Modules\GamificationEngine\Services;

use App\Services\Quiz\QuizSessionClaimService;
use Modules\GamificationEngine\Models\GamificationWallet;

/**
 * Finds or creates the wallet for a user id or guest cookie token.
 */
class GamificationWalletResolver
{
    public function __construct(
        private readonly QuizSessionClaimService $guestService,
    ) {}

    /** Resolve existing wallet or create empty one (guest token from cookie if omitted). */
    public function resolve(?int $userId, ?string $guestToken = null): GamificationWallet
    {
        $existing = $this->find($userId, $guestToken);

        if ($existing !== null) {
            return $existing;
        }

        if ($userId === null && ($guestToken === null || $guestToken === '')) {
            $guestToken = $this->guestService->ensureGuestToken();
        }

        return GamificationWallet::query()->create([
            'user_id' => $userId,
            'guest_token' => $userId === null ? $guestToken : null,
            'badges' => [],
            'perks' => [],
            'metadata' => [],
        ]);
    }

    /** Find without creating; null when no user and no guest token. */
    public function find(?int $userId, ?string $guestToken = null): ?GamificationWallet
    {
        if ($userId !== null) {
            return GamificationWallet::query()->where('user_id', $userId)->first();
        }

        if ($guestToken === null || $guestToken === '') {
            $guestToken = request()->cookie('egomap_guest');
        }

        if ($guestToken === null || $guestToken === '') {
            return null;
        }

        return GamificationWallet::query()->where('guest_token', $guestToken)->first();
    }
}
