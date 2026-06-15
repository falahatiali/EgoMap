<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherUserCheckIn;

class AetherCheckInService
{
    /**
     * Determine whether a weekly check-in is due for this program.
     *
     * A check-in is due when:
     * - The program started at least 7 days ago (first full week elapsed), AND
     * - No check-in exists for the current program week.
     *
     * @return array{is_due: bool, current_week: int, last_check_in_date: string|null}
     */
    public function checkInStatus(User $user, AetherGeneratedProgram $program): array
    {
        $currentWeek = $this->currentProgramWeek($program);

        $lastCheckIn = AetherUserCheckIn::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->latest('check_in_date')
            ->first();

        $isDue = $currentWeek >= 2 && ! $this->hasCheckInForWeek($user, $program, $currentWeek - 1);

        return [
            'is_due' => $isDue,
            'current_week' => $currentWeek,
            'last_check_in_date' => $lastCheckIn?->check_in_date?->toDateString(),
        ];
    }

    /**
     * Save a weekly check-in for the user.
     *
     * @param  array{sessions_completed: int, intensity_rating: int, had_pain: bool, pain_notes: string|null}  $data
     */
    public function saveCheckIn(User $user, AetherGeneratedProgram $program, array $data): AetherUserCheckIn
    {
        $week = $this->currentProgramWeek($program);

        return AetherUserCheckIn::query()->create([
            'user_id' => $user->id,
            'aether_generated_program_id' => $program->id,
            'check_in_date' => now()->toDateString(),
            // Map sessions_completed → workout_adherence_percent:
            // Assume 4-day/week program as baseline (adjustable).
            'workout_adherence_percent' => min(100, (int) round(($data['sessions_completed'] / 4) * 100)),
            // intensity_rating: 1=too easy, 2=just right, 3=too hard
            'energy_level' => match ($data['intensity_rating']) {
                1 => 8,  // too easy → high energy
                3 => 3,  // too hard → low energy
                default => 6,
            },
            'soreness_level' => $data['had_pain'] ? 7 : 3,
            'pain_points' => $data['pain_notes'] ? [$data['pain_notes']] : null,
            'feedback' => json_encode([
                'week' => $week,
                'sessions_completed' => $data['sessions_completed'],
                'intensity_rating' => $data['intensity_rating'],
                'had_pain' => $data['had_pain'],
            ]),
        ]);
    }

    /**
     * Build a coaching response based on the check-in answers.
     *
     * @param  array{sessions_completed: int, intensity_rating: int, had_pain: bool, pain_notes: string|null}  $data
     * @return array{message: string, adjustment_hint: string|null}
     */
    public function coachingResponse(array $data): array
    {
        $sessions = $data['sessions_completed'];
        $intensity = $data['intensity_rating'];
        $hadPain = $data['had_pain'];

        // Build a motivational, actionable response.
        if ($hadPain) {
            return [
                'message' => 'Pain is your body signalling. Rest a day, drop the load by 10%, and focus on form.',
                'adjustment_hint' => 'Consider substituting any painful movements next week.',
            ];
        }

        if ($sessions >= 4 && $intensity === 2) {
            return [
                'message' => 'Perfect week. 💪 You\'re right on track — keep the momentum!',
                'adjustment_hint' => null,
            ];
        }

        if ($sessions >= 4 && $intensity === 1) {
            return [
                'message' => 'You showed up every day and still felt strong. Time to push harder next week!',
                'adjustment_hint' => 'Try adding one extra set per exercise next week.',
            ];
        }

        if ($intensity === 3) {
            return [
                'message' => 'Tough week — that\'s growth. Make sure you\'re sleeping 7+ hours and eating enough.',
                'adjustment_hint' => 'Reduce volume by 20% next week so your body can absorb the work.',
            ];
        }

        if ($sessions <= 2) {
            return [
                'message' => 'Life happens. Even two sessions is better than zero — you kept your habit alive.',
                'adjustment_hint' => 'Aim for one extra session next week. Consistency beats intensity.',
            ];
        }

        return [
            'message' => 'Solid effort this week. Keep showing up!',
            'adjustment_hint' => null,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Returns 1-based program week derived from starts_at.
     * Falls back to current_week stored on the model if no start date is set.
     */
    public function currentProgramWeek(AetherGeneratedProgram $program): int
    {
        if ($program->starts_at !== null) {
            $daysSinceStart = (int) Carbon::parse($program->starts_at)->diffInDays(now(), absolute: true);

            return max(1, (int) ceil(($daysSinceStart + 1) / 7));
        }

        return max(1, (int) ($program->current_week ?? 1));
    }

    private function hasCheckInForWeek(User $user, AetherGeneratedProgram $program, int $week): bool
    {
        if ($program->starts_at === null) {
            // Can't determine week boundaries without a start date.
            // Fall back to "has any check-in in the last 7 days".
            return AetherUserCheckIn::query()
                ->where('user_id', $user->id)
                ->where('aether_generated_program_id', $program->id)
                ->where('check_in_date', '>=', now()->subDays(7)->toDateString())
                ->exists();
        }

        $weekStart = Carbon::parse($program->starts_at)->addWeeks($week - 1)->toDateString();
        $weekEnd = Carbon::parse($program->starts_at)->addWeeks($week)->subDay()->toDateString();

        return AetherUserCheckIn::query()
            ->where('user_id', $user->id)
            ->where('aether_generated_program_id', $program->id)
            ->whereBetween('check_in_date', [$weekStart, $weekEnd])
            ->exists();
    }
}
