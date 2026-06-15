<?php

namespace Modules\VirtueEngine\Services;

use Modules\VirtueEngine\Models\VirtueHabit;
use Modules\VirtueEngine\Models\VirtueRoutine;
use Modules\VirtueEngine\Models\VirtueSuccessLog;

class VirtueApiService
{
    /**
     * @return array<string, mixed>
     */
    public function habitPayload(VirtueHabit $habit): array
    {
        return [
            'id' => $habit->id,
            'slug' => $habit->slug,
            'name' => $habit->name,
            'category' => $habit->category->value,
            'category_label' => $habit->category->label(),
            'category_icon' => $habit->category->icon(),
            'description' => $habit->description,
            'ai_root_cause' => $habit->ai_root_cause,
            'ai_steps' => $habit->ai_steps ?? [],
            'ai_affirmation' => $habit->ai_affirmation,
            'is_predefined' => $habit->is_predefined,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function routinePayload(VirtueRoutine $routine): array
    {
        $habit = $routine->habit;

        return [
            'id' => $routine->id,
            'uuid' => $routine->uuid,
            'status' => $routine->status->value,
            'goal_type' => $routine->goal_type->value,
            'goal_target' => $routine->goal_target,
            'current_streak' => $routine->current_streak,
            'best_streak' => $routine->best_streak,
            'total_successes' => $routine->total_successes,
            'total_slips' => $routine->total_slips,
            'progress_percent' => $routine->progressPercent(),
            'personal_note' => $routine->personal_note,
            'last_success_date' => $routine->last_success_date?->toDateString(),
            'last_slip_date' => $routine->last_slip_date?->toDateString(),
            'completed_at' => $routine->completed_at?->toIso8601String(),
            'created_at' => $routine->created_at->toIso8601String(),
            'habit' => $habit ? $this->habitPayload($habit) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function successLogPayload(VirtueSuccessLog $log): array
    {
        return [
            'id' => $log->id,
            'situation' => $log->situation,
            'emotional_state' => $log->emotional_state,
            'ai_encouragement' => $log->ai_encouragement,
            'points_earned' => $log->points_earned,
            'logged_at' => $log->logged_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function routineProgressPayload(VirtueRoutine $routine): array
    {
        $recentSuccesses = $routine->successLogs()
            ->latest('logged_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => $this->successLogPayload($log))
            ->values()
            ->all();

        return array_merge($this->routinePayload($routine), [
            'recent_successes' => $recentSuccesses,
        ]);
    }
}
