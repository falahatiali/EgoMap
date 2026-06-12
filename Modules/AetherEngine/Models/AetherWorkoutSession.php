<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AetherEngine\Enums\WorkoutSessionStatus;

#[Fillable([
    'user_id',
    'aether_generated_program_id',
    'aether_program_workout_day_id',
    'scheduled_for',
    'started_at',
    'completed_at',
    'status',
    'energy_level',
    'mood_level',
    'pain_level',
    'difficulty_rating',
    'user_feedback',
    'metadata',
])]
class AetherWorkoutSession extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => WorkoutSessionStatus::class,
            'energy_level' => 'integer',
            'mood_level' => 'integer',
            'pain_level' => 'integer',
            'difficulty_rating' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AetherGeneratedProgram::class, 'aether_generated_program_id');
    }

    public function workoutDay(): BelongsTo
    {
        return $this->belongsTo(AetherProgramWorkoutDay::class, 'aether_program_workout_day_id');
    }

    public function setLogs(): HasMany
    {
        return $this->hasMany(AetherWorkoutSetLog::class, 'aether_workout_session_id');
    }
}
