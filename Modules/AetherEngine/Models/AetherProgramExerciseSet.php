<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AetherEngine\Enums\ExerciseSetType;

#[Fillable([
    'aether_program_exercise_id',
    'set_number',
    'set_type',
    'target_reps_min',
    'target_reps_max',
    'target_weight_kg',
    'target_rpe',
    'target_rir',
    'rest_seconds',
    'tempo',
    'notes',
])]
class AetherProgramExerciseSet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'set_type' => ExerciseSetType::class,
            'target_reps_min' => 'integer',
            'target_reps_max' => 'integer',
            'target_weight_kg' => 'decimal:2',
            'target_rpe' => 'integer',
            'target_rir' => 'integer',
            'rest_seconds' => 'integer',
        ];
    }

    public function programExercise(): BelongsTo
    {
        return $this->belongsTo(AetherProgramExercise::class, 'aether_program_exercise_id');
    }

    public function setLogs(): HasMany
    {
        return $this->hasMany(AetherWorkoutSetLog::class, 'aether_program_exercise_set_id');
    }
}
