<?php

namespace Modules\VirtueEngine\Services;

use App\Models\User;
use Carbon\Carbon;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationPunishmentService;
use Modules\VirtueEngine\Enums\VirtueGoalType;
use Modules\VirtueEngine\Enums\VirtueRoutineStatus;
use Modules\VirtueEngine\Models\VirtueRoutine;
use Modules\VirtueEngine\Models\VirtueSlipLog;
use Modules\VirtueEngine\Models\VirtueSuccessLog;

class VirtueProgressService
{
    public function __construct(
        private readonly GamificationEngine $gamification,
        private readonly GamificationPunishmentService $punishments,
        private readonly VirtueAIService $ai,
    ) {}

    /**
     * Log a success moment and award points.
     *
     * @param  array{situation?: string|null, emotional_state?: string|null}  $data
     * @return array{success_log: VirtueSuccessLog, gamification: array<string, mixed>, routine_completed: bool}
     */
    public function logSuccess(VirtueRoutine $routine, User $user, array $data): array
    {
        $today = Carbon::today()->toDateString();

        $situation = $data['situation'] ?? null;
        $aiEncouragement = null;

        if ($situation && strlen($situation) > 5) {
            $aiEncouragement = $this->ai->encourageSuccess(
                $routine->habit->description ?? $routine->habit->name,
                $situation,
            );
        }

        $log = VirtueSuccessLog::query()->create([
            'virtue_routine_id' => $routine->id,
            'user_id' => $user->id,
            'situation' => $situation,
            'emotional_state' => $data['emotional_state'] ?? null,
            'ai_encouragement' => $aiEncouragement,
            'points_earned' => (int) config('virtue.points.success_logged', 5),
            'logged_at' => now(),
        ]);

        $routine->increment('total_successes');

        $this->updateStreak($routine, $today);

        $gamificationResult = $this->gamification->dispatch(
            GamificationEvent::VirtueSuccessLogged->value,
            ['user_id' => $user->id],
        );

        if ($routine->current_streak === 7) {
            $this->gamification->dispatch(
                GamificationEvent::VirtueStreak7->value,
                ['user_id' => $user->id],
            );
        }

        $routineCompleted = $this->checkAndCompleteRoutine($routine);

        return [
            'success_log' => $log,
            'gamification' => $gamificationResult,
            'routine_completed' => $routineCompleted,
        ];
    }

    /**
     * Log a slip, dispatch penalty via Gamification, and optionally assign a punishment.
     *
     * @param  array{what_happened?: string|null, choose_punishment_id?: int|null}  $data
     * @return array{slip_log: VirtueSlipLog, gamification: array<string, mixed>, punishment_suggestions: list<array<string, mixed>>, ai_response: array<string, mixed>|null}
     */
    public function logSlip(VirtueRoutine $routine, User $user, array $data): array
    {
        $whatHappened = $data['what_happened'] ?? 'Did not specify';

        $aiResponse = $this->ai->generateSlipResponse(
            $routine->habit->description ?? $routine->habit->name,
            $whatHappened,
        );

        $log = VirtueSlipLog::query()->create([
            'virtue_routine_id' => $routine->id,
            'user_id' => $user->id,
            'what_happened' => $whatHappened,
            'ai_personalized_punishment' => $aiResponse ? $aiResponse['micro_task'] : null,
            'logged_at' => now(),
        ]);

        $routine->increment('total_slips');
        $routine->update(['last_slip_date' => today(), 'current_streak' => 0]);

        $gamificationResult = $this->gamification->dispatch(
            GamificationEvent::VirtueSlipReported->value,
            ['user_id' => $user->id, 'metadata' => ['trigger' => 'virtue_slip']],
        );

        $punishmentSuggestions = $this->punishments->suggest('virtue_slip', $user->id);

        if (isset($data['choose_punishment_id']) && is_int($data['choose_punishment_id'])) {
            $assigned = $this->punishments->assign($user, $data['choose_punishment_id'], null, 'virtue_slip');
            $log->update(['gamification_user_punishment_id' => $assigned->id]);
        }

        return [
            'slip_log' => $log,
            'gamification' => $gamificationResult,
            'punishment_suggestions' => $punishmentSuggestions,
            'ai_response' => $aiResponse,
        ];
    }

    /**
     * Manually mark a routine as completed and grant the completion reward.
     *
     * @return array{gamification: array<string, mixed>}
     */
    public function completeRoutine(VirtueRoutine $routine, User $user): array
    {
        $routine->update([
            'status' => VirtueRoutineStatus::Completed,
            'completed_at' => now(),
        ]);

        $gamificationResult = $this->gamification->dispatch(
            GamificationEvent::VirtueRoutineCompleted->value,
            ['user_id' => $user->id],
        );

        return ['gamification' => $gamificationResult];
    }

    private function updateStreak(VirtueRoutine $routine, string $today): void
    {
        $lastDate = $routine->last_success_date?->toDateString();

        if ($lastDate === null || $lastDate === Carbon::yesterday()->toDateString()) {
            $routine->increment('current_streak');
        } elseif ($lastDate !== $today) {
            $routine->update(['current_streak' => 1]);
        }

        $routine->refresh();

        if ($routine->current_streak > $routine->best_streak) {
            $routine->update(['best_streak' => $routine->current_streak]);
        }

        $routine->update(['last_success_date' => $today]);
    }

    private function checkAndCompleteRoutine(VirtueRoutine $routine): bool
    {
        if ($routine->status !== VirtueRoutineStatus::Active) {
            return false;
        }

        $goalReached = match ($routine->goal_type) {
            VirtueGoalType::DaysCount => $routine->current_streak >= $routine->goal_target,
            VirtueGoalType::SuccessCount => $routine->total_successes >= $routine->goal_target,
        };

        if ($goalReached) {
            $routine->update([
                'status' => VirtueRoutineStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->gamification->dispatch(
                GamificationEvent::VirtueRoutineCompleted->value,
                ['user_id' => $routine->user_id],
            );

            return true;
        }

        return false;
    }
}
