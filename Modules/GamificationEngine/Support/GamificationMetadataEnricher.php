<?php

namespace Modules\GamificationEngine\Support;

use Carbon\CarbonImmutable;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationWallet;

/**
 * Merges live wallet state into dispatch metadata so chained rules can match milestones.
 */
class GamificationMetadataEnricher
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function enrichFromWallet(GamificationWallet $wallet, string $event, array $metadata): array
    {
        $walletMeta = is_array($wallet->metadata) ? $wallet->metadata : [];

        $metadata['streak_days'] = $wallet->streak_days;
        $metadata['blackhole_writes_total'] = (int) ($walletMeta['blackhole_writes_total'] ?? 0);
        $metadata['blackhole_tier'] = (int) ($walletMeta['blackhole_tier'] ?? 0);
        $metadata['blackhole_streak_days'] = (int) ($walletMeta['blackhole_streak_days'] ?? 0);

        if ($event === GamificationEvent::GhostModeBlackholeWrite->value) {
            $metadata['writes_today'] = $this->blackholeWritesToday($wallet) + 1;
        }

        if (! array_key_exists('emergency_count', $metadata)
            && $event === GamificationEvent::GhostModeEmergencyCompleted->value) {
            $metadata['emergency_count'] = $this->emergencyUsesToday($wallet) + 1;
        }

        return $metadata;
    }

    private function blackholeWritesToday(GamificationWallet $wallet): int
    {
        return $wallet->transactions()
            ->where('event', GamificationEvent::GhostModeBlackholeWrite->value)
            ->whereDate('created_at', CarbonImmutable::today())
            ->count();
    }

    private function emergencyUsesToday(GamificationWallet $wallet): int
    {
        return $wallet->transactions()
            ->where('event', GamificationEvent::GhostModeEmergencyCompleted->value)
            ->whereDate('created_at', CarbonImmutable::today())
            ->count();
    }
}
