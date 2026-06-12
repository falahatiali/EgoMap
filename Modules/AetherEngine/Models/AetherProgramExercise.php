<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'aether_program_workout_day_id',
    'aether_exercise_id',
    'sort_order',
    'prescription_type',
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

    public function libraryExercise(): BelongsTo
    {
        return $this->belongsTo(AetherExercise::class, 'aether_exercise_id');
    }

    public function prescriptionSets(): HasMany
    {
        return $this->hasMany(AetherProgramExerciseSet::class, 'aether_program_exercise_id')->orderBy('set_number');
    }
}
