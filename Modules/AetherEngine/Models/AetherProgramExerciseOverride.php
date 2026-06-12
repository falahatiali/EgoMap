<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AetherEngine\Enums\MuscleGroup;

#[Fillable([
    'user_id',
    'aether_program_exercise_id',
    'slug',
    'name',
    'muscle_group',
    'sets',
    'reps',
    'rest_seconds',
    'notes',
])]
class AetherProgramExerciseOverride extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sets' => 'integer',
            'rest_seconds' => 'integer',
            'muscle_group' => MuscleGroup::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programExercise(): BelongsTo
    {
        return $this->belongsTo(AetherProgramExercise::class, 'aether_program_exercise_id');
    }
}
