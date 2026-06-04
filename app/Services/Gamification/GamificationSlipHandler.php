<?php

namespace App\Services\Gamification;

use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationPunishmentService;

/**
 * Records slips with optional consumable discount perk (slip_discount_50).
 */
readonly class GamificationSlipHandler
{
    private const DiscountPerk = 'slip_discount_50';

    public function __construct(
        private GamificationEngine $gamification,
        private GamificationPunishmentService $punishments,
    ) {}

    /**
     * @param  array{user_id?: ?int, guest_token?: ?string, metadata?: array<string, mixed>}  $actorContext
     * @return array<string, mixed>
     */
    public function record(string $slipTrigger, array $actorContext): array
    {
        $user = isset($actorContext['user_id'])
            ? \App\Models\User::query()->find($actorContext['user_id'])
            : auth()->user();

        $wallet = $this->gamification->walletFor($user, $actorContext['guest_token'] ?? null);

        $perks = is_array($wallet['perks'] ?? null) ? $wallet['perks'] : [];
        $usesDiscount = in_array(self::DiscountPerk, $perks, true);

        $metadata = array_merge($actorContext['metadata'] ?? [], [
            'trigger' => $slipTrigger,
            'apply_penalty' => ! $usesDiscount,
            'discounted' => $usesDiscount,
        ]);

        if ($usesDiscount) {
            $this->gamification->consumePerk(self::DiscountPerk, $actorContext);
        }

        $result = $this->gamification->dispatch(
            GamificationEvent::GhostModeSlipReported->value,
            [
                'user_id' => $actorContext['user_id'] ?? null,
                'guest_token' => $actorContext['guest_token'] ?? null,
                'metadata' => $metadata,
            ],
        );

        $userId = $actorContext['user_id'] ?? auth()->id();
        $result['suggested_punishments'] = $userId !== null
            ? $this->punishments->suggest($slipTrigger, (int) $userId)
            : [];

        return $result;
    }
}
