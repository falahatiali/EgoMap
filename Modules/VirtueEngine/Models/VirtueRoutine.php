<?php

namespace Modules\VirtueEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\VirtueEngine\Enums\VirtueGoalType;
use Modules\VirtueEngine\Enums\VirtueRoutineStatus;

#[Fillable([
    'uuid', 'user_id', 'virtue_habit_id', 'personal_note',
    'goal_type', 'goal_target', 'current_streak', 'best_streak',
    'total_successes', 'total_slips', 'status',
    'last_success_date', 'last_slip_date', 'completed_at',
])]
class VirtueRoutine extends Model
{
    protected function casts(): array
    {
        return [
            'goal_type' => VirtueGoalType::class,
            'status' => VirtueRoutineStatus::class,
            'last_success_date' => 'date',
            'last_slip_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VirtueRoutine $routine): void {
            if (empty($routine->uuid)) {
                $routine->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(VirtueHabit::class, 'virtue_habit_id');
    }

    public function successLogs(): HasMany
    {
        return $this->hasMany(VirtueSuccessLog::class);
    }

    public function slipLogs(): HasMany
    {
        return $this->hasMany(VirtueSlipLog::class);
    }

    public function progressPercent(): float
    {
        if ($this->goal_target === 0) {
            return 0.0;
        }

        $current = match ($this->goal_type) {
            VirtueGoalType::DaysCount => $this->current_streak,
            VirtueGoalType::SuccessCount => $this->total_successes,
        };

        return min(100.0, round(($current / $this->goal_target) * 100, 1));
    }

    public function isCompleted(): bool
    {
        return $this->status === VirtueRoutineStatus::Completed;
    }
}
