<?php

namespace App\Services\NoContact;

use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Models\User;
use App\Services\Locale\LocaleDigitFormatter;
use App\Services\Quiz\QuizSessionClaimService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NoContactTimerService
{
    public function __construct(
        private readonly QuizSessionClaimService $guestService,
        private readonly LocaleDigitFormatter $digits,
    ) {}

    /**
     * @return list<array{days: int, label: string, description: string, recommended: bool}>
     */
    public function presets(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return collect(config('no_contact.presets', []))
            ->map(function (array $preset) use ($locale): array {
                $days = (int) ($preset['days'] ?? 0);

                return [
                    'days' => $days,
                    'label' => __((string) ($preset['label_key'] ?? ''), locale: $locale),
                    'description' => __((string) ($preset['description_key'] ?? ''), locale: $locale),
                    'recommended' => (bool) ($preset['recommended'] ?? false),
                ];
            })
            ->filter(fn (array $preset): bool => $preset['days'] > 0)
            ->values()
            ->all();
    }

    public function recommendedDays(): int
    {
        return max((int) config('no_contact.recommended_days', 90), 1);
    }

    /**
     * @return array{user_id: ?int, guest_token: ?string}
     */
    public function resolveOwner(): array
    {
        if (auth()->check()) {
            return [
                'user_id' => auth()->id(),
                'guest_token' => null,
            ];
        }

        return [
            'user_id' => null,
            'guest_token' => $this->guestService->ensureGuestToken(),
        ];
    }

    public function findActiveProtocol(): ?NoContactProtocol
    {
        $owner = $this->resolveOwner();

        $protocol = $this->queryForOwner($owner)
            ->where('status', NoContactStatus::Active)
            ->latest('updated_at')
            ->first();

        if ($protocol === null) {
            return null;
        }

        return $this->syncCompletion($protocol);
    }

    /**
     * @return array<string, mixed>
     */
    public function displayState(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $now = CarbonImmutable::now();
        $protocol = $this->findActiveProtocol();

        if ($protocol === null) {
            return [
                'mode' => 'setup',
                'presets' => $this->presets($locale),
                'recommended_days' => $this->recommendedDays(),
                'server_now' => $now->toIso8601String(),
            ];
        }

        if ($protocol->status === NoContactStatus::Completed) {
            return $this->completedState($protocol, $locale, $now);
        }

        return $this->activeState($protocol, $locale, $now);
    }

    public function start(int $durationDays): NoContactProtocol
    {
        if ($durationDays < 1 || $durationDays > 365) {
            throw new InvalidArgumentException('Duration must be between 1 and 365 days.');
        }

        $owner = $this->resolveOwner();
        $now = CarbonImmutable::now();

        return DB::transaction(function () use ($owner, $durationDays, $now): NoContactProtocol {
            $this->archiveActiveProtocolsForOwner($owner, $now);

            return NoContactProtocol::query()->create([
                'user_id' => $owner['user_id'],
                'guest_token' => $owner['guest_token'],
                'duration_days' => $durationDays,
                'status' => NoContactStatus::Active,
                'streak_started_at' => $now,
                'target_ends_at' => $now->addDays($durationDays),
                'slip_count' => 0,
            ]);
        });
    }

    public function recordSlip(): NoContactProtocol
    {
        $protocol = $this->findActiveProtocol();

        if ($protocol === null || $protocol->status !== NoContactStatus::Active) {
            throw new InvalidArgumentException('No active no-contact protocol to reset.');
        }

        $now = CarbonImmutable::now();

        $protocol->update([
            'streak_started_at' => $now,
            'target_ends_at' => $now->addDays($protocol->duration_days),
            'slip_count' => $protocol->slip_count + 1,
            'last_slip_at' => $now,
        ]);

        return $protocol->refresh();
    }

    public function authorizeProtocol(NoContactProtocol $protocol): bool
    {
        $owner = $this->resolveOwner();

        if ($owner['user_id'] !== null) {
            return $protocol->user_id === $owner['user_id'];
        }

        return $protocol->user_id === null
            && $protocol->guest_token !== null
            && $protocol->guest_token === $owner['guest_token'];
    }

    public function claimForUser(User $user, ?string $guestToken = null): int
    {
        $guestToken ??= request()->cookie('egomap_guest');

        if ($guestToken === null || $guestToken === '') {
            return 0;
        }

        return NoContactProtocol::query()
            ->whereNull('user_id')
            ->where('guest_token', $guestToken)
            ->update(['user_id' => $user->id]);
    }

    private function syncCompletion(NoContactProtocol $protocol): NoContactProtocol
    {
        if ($protocol->status !== NoContactStatus::Active) {
            return $protocol;
        }

        if (CarbonImmutable::now()->greaterThanOrEqualTo($protocol->target_ends_at)) {
            $protocol->update([
                'status' => NoContactStatus::Completed,
                'completed_at' => CarbonImmutable::now(),
            ]);

            return $protocol->refresh();
        }

        return $protocol;
    }

    /**
     * @param  array{user_id: ?int, guest_token: ?string}  $owner
     */
    private function queryForOwner(array $owner)
    {
        return NoContactProtocol::query()
            ->when(
                $owner['user_id'] !== null,
                fn ($query) => $query->where('user_id', $owner['user_id']),
                fn ($query) => $query->where('guest_token', $owner['guest_token']),
            );
    }

    /**
     * @param  array{user_id: ?int, guest_token: ?string}  $owner
     */
    private function archiveActiveProtocolsForOwner(array $owner, CarbonImmutable $now): void
    {
        $this->queryForOwner($owner)
            ->where('status', NoContactStatus::Active)
            ->update([
                'status' => NoContactStatus::Completed,
                'completed_at' => $now,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function activeState(NoContactProtocol $protocol, string $locale, CarbonImmutable $now): array
    {
        $startedAt = CarbonImmutable::parse($protocol->streak_started_at);
        $endsAt = CarbonImmutable::parse($protocol->target_ends_at);
        $totalSeconds = max($endsAt->getTimestamp() - $startedAt->getTimestamp(), 1);
        $remainingSeconds = max(0, $endsAt->getTimestamp() - $now->getTimestamp());
        $elapsedSeconds = max(0, $totalSeconds - $remainingSeconds);
        $progressPercent = (int) round(min(100, max(0, ($elapsedSeconds / $totalSeconds) * 100)));

        return [
            'mode' => 'active',
            'protocol_uuid' => $protocol->uuid,
            'duration_days' => $protocol->duration_days,
            'slip_count' => $protocol->slip_count,
            'streak_started_at' => $startedAt->toIso8601String(),
            'target_ends_at' => $endsAt->toIso8601String(),
            'server_now' => $now->toIso8601String(),
            'remaining_seconds' => $remainingSeconds,
            'elapsed_seconds' => $elapsedSeconds,
            'total_seconds' => $totalSeconds,
            'progress_percent' => $progressPercent,
            'countdown' => $this->formatCountdown($remainingSeconds),
            'elapsed_label' => $this->formatDurationLabel($elapsedSeconds, $locale),
            'presets' => $this->presets($locale),
            'recommended_days' => $this->recommendedDays(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completedState(NoContactProtocol $protocol, string $locale, CarbonImmutable $now): array
    {
        return [
            'mode' => 'completed',
            'protocol_uuid' => $protocol->uuid,
            'duration_days' => $protocol->duration_days,
            'slip_count' => $protocol->slip_count,
            'completed_at' => $protocol->completed_at?->toIso8601String(),
            'server_now' => $now->toIso8601String(),
            'presets' => $this->presets($locale),
            'recommended_days' => $this->recommendedDays(),
        ];
    }

    /**
     * @return array{days: int, hours: int, minutes: int, seconds: int, total_days: int}
     */
    private function formatCountdown(int $remainingSeconds): array
    {
        $days = intdiv($remainingSeconds, 86400);
        $hours = intdiv($remainingSeconds % 86400, 3600);
        $minutes = intdiv($remainingSeconds % 3600, 60);
        $seconds = $remainingSeconds % 60;

        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'total_days' => $days,
        ];
    }

    private function formatDurationLabel(int $seconds, string $locale): string
    {
        $days = intdiv($seconds, 86400);

        if ($days > 0) {
            return trans_choice('no_contact.elapsed_days', $days, [
                'count' => $this->digits->format($days, $locale),
            ], $locale);
        }

        $hours = intdiv($seconds, 3600);

        return trans_choice('no_contact.elapsed_hours', $hours, [
            'count' => $this->digits->format($hours, $locale),
        ], $locale);
    }
}
