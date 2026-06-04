<?php

namespace App\Services\NoContact;

use Carbon\CarbonImmutable;
use Modules\GamificationEngine\Services\GamificationEngine;

/**
 * Daily quote, mission state, and gentle missed check-in messaging for Ghost Mode.
 */
readonly class GhostModeDailyService
{
    public function __construct(
        private GamificationEngine $gamification,
    ) {}

    public function dailyQuote(): string
    {
        $quotes = config('gamification.ghost_mode.daily_quotes', []);

        if ($quotes === []) {
            return '';
        }

        $index = (int) CarbonImmutable::today()->format('z') % count($quotes);

        return (string) $quotes[$index];
    }

    /**
     * @param  array<string, mixed>  $wallet
     */
    public function missionCompletedToday(array $wallet): bool
    {
        $meta = is_array($wallet['metadata'] ?? null) ? $wallet['metadata'] : [];
        $today = CarbonImmutable::today()->toDateString();

        return ($meta['ghost_daily_mission_date'] ?? null) === $today;
    }

    /**
     * @param  array<string, mixed>  $wallet
     */
    public function blockConfirmedToday(array $wallet): bool
    {
        $meta = is_array($wallet['metadata'] ?? null) ? $wallet['metadata'] : [];
        $today = CarbonImmutable::today()->toDateString();

        return ($meta['ghost_block_confirmed_date'] ?? null) === $today;
    }

    /**
     * @param  array<string, mixed>  $wallet
     */
    public function gentleMissedCheckinMessage(array $wallet): ?string
    {
        $lastLogin = $wallet['last_login_date'] ?? null;
        $threshold = max(3, (int) config('gamification.punishments.missed_checkin_gentle_days', 3));

        if (! is_string($lastLogin) || $lastLogin === '') {
            return null;
        }

        $daysSince = CarbonImmutable::parse($lastLogin)->diffInDays(CarbonImmutable::today());

        if ($daysSince < $threshold) {
            return null;
        }

        return __('no_contact.gentle_missed_checkin', ['days' => $daysSince]);
    }

    /**
     * @return array<string, mixed>
     */
    public function walletSnapshotFor(?int $userId, ?string $guestToken): array
    {
        return $this->gamification->walletFor(
            $userId ? \App\Models\User::query()->find($userId) : null,
            $guestToken,
        );
    }
}
