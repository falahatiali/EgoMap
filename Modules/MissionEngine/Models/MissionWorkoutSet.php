<?php

namespace Modules\MissionEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workout_exercise_id',
    'set_number',
    'reps',
    'weight',
    'weight_unit',
    'duration_seconds',
    'rpe',
    'notes',
])]
class MissionWorkoutSet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'reps' => 'integer',
            'weight' => 'decimal:2',
            'duration_seconds' => 'integer',
            'rpe' => 'decimal:1',
        ];
    }

    /**
     * @return BelongsTo<MissionWorkoutExercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(MissionWorkoutExercise::class, 'workout_exercise_id');
    }
}
