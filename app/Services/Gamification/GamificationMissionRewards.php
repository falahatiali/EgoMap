<?php

namespace App\Services\Gamification;

use App\Models\User;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationWalletResolver;

/**
 * Call when a mission enrollment is marked completed.
 */
readonly class GamificationMissionRewards
{
    public function __construct(
        private GamificationEngine $gamification,
        private GamificationWalletResolver $wallets,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dispatchCompleted(
        User $user,
        int $missionId,
        string $difficulty = 'medium',
    ): array {
        $hardTotal = $this->countHardCompletions($user->id);

        if ($difficulty === 'hard') {
            $hardTotal++;
        }

        return $this->gamification->dispatch(
            GamificationEvent::MissionCompleted->value,
            [
                'user_id' => $user->id,
                'metadata' => [
                    'mission_id' => $missionId,
                    'difficulty' => $difficulty,
                    'hard_completions_total' => $hardTotal,
                ],
            ],
        );
    }

    private function countHardCompletions(int $userId): int
    {
        $wallet = $this->wallets->find($userId, null);

        if ($wallet === null) {
            return 0;
        }

        return $wallet->transactions()
            ->where('event', GamificationEvent::MissionCompleted->value)
            ->get()
            ->filter(fn ($tx): bool => ($tx->metadata['difficulty'] ?? '') === 'hard')
            ->count();
    }
}
