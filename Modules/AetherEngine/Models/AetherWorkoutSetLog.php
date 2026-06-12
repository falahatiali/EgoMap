<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'aether_workout_session_id',
    'aether_program_exercise_set_id',
    'completed_reps',
    'weight_kg',
    'perceived_exertion',
    'pain_level',
    'completed',
    'skipped',
    'completed_at',
])]
class AetherWorkoutSetLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_reps' => 'integer',
            'weight_kg' => 'decimal:2',
            'perceived_exertion' => 'integer',
            'pain_level' => 'integer',
            'completed' => 'boolean',
            'skipped' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AetherWorkoutSession::class, 'aether_workout_session_id');
    }

    public function programExerciseSet(): BelongsTo
    {
        return $this->belongsTo(AetherProgramExerciseSet::class, 'aether_program_exercise_set_id');
    }
}
