<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'aether_program_workout_day_id',
    'sort_order',
    'slug',
    'name',
    'muscle_group',
    'sets',
    'reps',
    'rest_seconds',
    'notes',
    'alternative_slugs',
])]
class AetherProgramExercise extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'sets' => 'integer',
            'rest_seconds' => 'integer',
            'alternative_slugs' => 'array',
        ];
    }

    public function workoutDay(): BelongsTo
    {
        return $this->belongsTo(AetherProgramWorkoutDay::class, 'aether_program_workout_day_id');
    }
}
