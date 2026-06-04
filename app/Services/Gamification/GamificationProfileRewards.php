<?php

namespace App\Services\Gamification;

use App\Models\User;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;

/**
 * Dispatches profile.updated when user profile / triage data changes.
 */
readonly class GamificationProfileRewards
{
    public function __construct(
        private GamificationEngine $gamification,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dispatchForUser(User $user, int $completenessPercent, string $changedField = 'profile'): array
    {
        return $this->gamification->dispatch(
            GamificationEvent::ProfileUpdated->value,
            [
                'user_id' => $user->id,
                'metadata' => [
                    'completeness' => $completenessPercent,
                    'complete' => $completenessPercent >= 100,
                    'field' => $changedField,
                ],
            ],
        );
    }

    public function completenessForUser(User $user): int
    {
        $fields = [
            $user->name,
            $user->email,
            $user->relationship_duration,
            $user->breakup_duration,
            $user->breakup_initiator,
            $user->primary_struggle,
            $user->recovery_phase,
        ];

        $filled = collect($fields)->filter(fn ($value): bool => $value !== null && $value !== '')->count();

        return (int) round(($filled / max(1, count($fields))) * 100);
    }
}
