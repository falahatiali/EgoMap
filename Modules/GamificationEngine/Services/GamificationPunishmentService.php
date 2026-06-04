<?php

namespace Modules\GamificationEngine\Services;

use App\Models\NoContactProtocol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Enums\GamificationPunishmentType;
use Modules\GamificationEngine\Enums\GamificationUserPunishmentStatus;
use Modules\GamificationEngine\Models\GamificationPunishment;
use Modules\GamificationEngine\Models\GamificationUserPunishment;

/**
 * User-chosen recovery tasks after a slip; physical actions respect daily/hourly cooldowns.
 */
class GamificationPunishmentService
{
    public function __construct(
        private readonly GamificationEngine $gamification,
        private readonly GamificationWalletResolver $wallets,
    ) {}

    public function slipSeverity(string $trigger): int
    {
        return (int) config("gamification.punishments.slip_severity.{$trigger}", 2);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function suggest(string $slipTrigger, ?int $userId): array
    {
        $severity = $this->slipSeverity($slipTrigger);
        $count = max(3, (int) config('gamification.punishments.suggestions_count', 4));
        $canPhysical = $userId !== null && $this->canOfferPhysical($userId);

        $candidates = GamificationPunishment::query()
            ->where('is_active', true)
            ->where('min_slip_severity', '<=', $severity)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $physical = $candidates->filter(fn (GamificationPunishment $p) => $p->type === GamificationPunishmentType::Physical);
        $nonPhysical = $candidates->reject(fn (GamificationPunishment $p) => $p->type === GamificationPunishmentType::Physical);

        $picked = collect();

        if ($canPhysical && $physical->isNotEmpty()) {
            $picked = $picked->merge($physical->shuffle()->take(2));
        }

        $remaining = $count - $picked->count();
        if ($remaining > 0) {
            $picked = $picked->merge($nonPhysical->shuffle()->take($remaining));
        }

        if ($picked->count() < $count) {
            $picked = $picked->merge(
                $candidates->whereNotIn('id', $picked->pluck('id'))->take($count - $picked->count()),
            );
        }

        return $picked->unique('id')->take($count)->values()->map(fn (GamificationPunishment $p) => $this->punishmentPayload($p))->all();
    }

    public function assign(
        User $user,
        int $punishmentId,
        ?NoContactProtocol $protocol,
        string $slipTrigger,
    ): GamificationUserPunishment {
        $punishment = GamificationPunishment::query()->where('is_active', true)->findOrFail($punishmentId);

        if ($punishment->type === GamificationPunishmentType::Physical) {
            $this->assertCanOfferPhysical($user->id);
        }

        GamificationUserPunishment::query()
            ->where('user_id', $user->id)
            ->where('status', GamificationUserPunishmentStatus::Pending)
            ->update(['status' => GamificationUserPunishmentStatus::Expired]);

        $record = GamificationUserPunishment::query()->create([
            'user_id' => $user->id,
            'gamification_punishment_id' => $punishment->id,
            'no_contact_protocol_id' => $protocol?->id,
            'slip_trigger' => $slipTrigger,
            'status' => GamificationUserPunishmentStatus::Pending,
            'assigned_at' => now(),
            'metadata' => [
                'punishment_slug' => $punishment->slug,
            ],
        ]);

        if ($punishment->type === GamificationPunishmentType::Physical) {
            $this->recordPhysicalAssigned($user->id);
        }

        return $record->load('punishment');
    }

    /**
     * @return array{user_punishment: array<string, mixed>, gamification: array<string, mixed>}
     */
    public function complete(GamificationUserPunishment $userPunishment): array
    {
        if ($userPunishment->status !== GamificationUserPunishmentStatus::Pending) {
            return [
                'user_punishment' => $this->userPunishmentPayload($userPunishment->load('punishment')),
                'gamification' => [],
            ];
        }

        $punishment = $userPunishment->punishment;
        $recoveryPercent = max(10, min(100, (int) config('gamification.punishments.recovery_percent', 50)));
        $severity = $this->slipSeverity((string) ($userPunishment->slip_trigger ?? 'other'));
        $basePoints = abs($punishment->points) > 0
            ? abs($punishment->points)
            : (int) config("gamification.punishments.recovery_points_by_severity.{$severity}", 5);
        $baseCoins = abs($punishment->coins) > 0
            ? abs($punishment->coins)
            : (int) config("gamification.punishments.recovery_coins_by_severity.{$severity}", 1);
        $pointsBack = (int) round($basePoints * ($recoveryPercent / 100));
        $coinsBack = (int) round($baseCoins * ($recoveryPercent / 100));

        $userPunishment->update([
            'status' => GamificationUserPunishmentStatus::Completed,
            'completed_at' => now(),
            'metadata' => array_merge($userPunishment->metadata ?? [], [
                'recovery_percent' => $recoveryPercent,
                'points_recovered' => $pointsBack,
                'coins_recovered' => $coinsBack,
            ]),
        ]);

        $result = $this->gamification->dispatch(
            GamificationEvent::PunishmentCompleted->value,
            [
                'user_id' => $userPunishment->user_id,
                'metadata' => [
                    'punishment_slug' => $punishment->slug,
                    'points_recovered' => $pointsBack,
                    'coins_recovered' => $coinsBack,
                    'recovery_percent' => $recoveryPercent,
                ],
            ],
        );

        return [
            'user_punishment' => $this->userPunishmentPayload($userPunishment->fresh()->load('punishment')),
            'gamification' => $result,
        ];
    }

    public function pendingFor(?int $userId): ?GamificationUserPunishment
    {
        if ($userId === null) {
            return null;
        }

        return GamificationUserPunishment::query()
            ->with('punishment')
            ->where('user_id', $userId)
            ->where('status', GamificationUserPunishmentStatus::Pending)
            ->latest('assigned_at')
            ->first();
    }

    public function canOfferPhysical(int $userId): bool
    {
        try {
            $this->assertCanOfferPhysical($userId);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function assertCanOfferPhysical(int $userId): void
    {
        $wallet = $this->wallets->resolve($userId, null);
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $physical = is_array($meta['physical_punishments'] ?? null) ? $meta['physical_punishments'] : [];

        $today = CarbonImmutable::today()->toDateString();
        $countToday = (int) ($physical['count_'.$today] ?? 0);
        $maxPerDay = max(1, (int) config('gamification.punishments.physical_max_per_day', 2));

        if ($countToday >= $maxPerDay) {
            throw new \InvalidArgumentException('Physical punishment daily limit reached.');
        }

        $lastAt = $physical['last_at'] ?? null;
        if (is_string($lastAt) && $lastAt !== '') {
            $cooldownHours = max(1, (int) config('gamification.punishments.physical_cooldown_hours', 2));
            if (CarbonImmutable::parse($lastAt)->addHours($cooldownHours)->isFuture()) {
                throw new \InvalidArgumentException('Physical punishment cooldown active.');
            }
        }
    }

    private function recordPhysicalAssigned(int $userId): void
    {
        $wallet = $this->wallets->resolve($userId, null);
        $meta = is_array($wallet->metadata) ? $wallet->metadata : [];
        $physical = is_array($meta['physical_punishments'] ?? null) ? $meta['physical_punishments'] : [];
        $today = CarbonImmutable::today()->toDateString();
        $key = 'count_'.$today;
        $physical[$key] = (int) ($physical[$key] ?? 0) + 1;
        $physical['last_at'] = CarbonImmutable::now()->toIso8601String();
        $meta['physical_punishments'] = $physical;
        $wallet->metadata = $meta;
        $wallet->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function punishmentPayload(GamificationPunishment $punishment): array
    {
        return [
            'id' => $punishment->id,
            'slug' => $punishment->slug,
            'title' => $punishment->title,
            'description' => $punishment->description,
            'type' => $punishment->type->value,
            'difficulty' => $punishment->difficulty->value,
            'points' => $punishment->points,
            'coins' => $punishment->coins,
            'estimated_minutes' => $punishment->estimated_minutes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userPunishmentPayload(GamificationUserPunishment $record): array
    {
        $punishment = $record->punishment;

        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'status' => $record->status->value,
            'assigned_at' => $record->assigned_at?->toIso8601String(),
            'completed_at' => $record->completed_at?->toIso8601String(),
            'slip_trigger' => $record->slip_trigger,
            'punishment' => $punishment ? $this->punishmentPayload($punishment) : null,
            'metadata' => $record->metadata ?? [],
        ];
    }
}
