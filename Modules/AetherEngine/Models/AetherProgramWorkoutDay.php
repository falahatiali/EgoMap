<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'aether_generated_program_id',
    'day_index',
    'label',
    'focus',
    'warmup',
    'cooldown',
    'motivation',
])]
class AetherProgramWorkoutDay extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_index' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AetherGeneratedProgram::class, 'aether_generated_program_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(AetherProgramExercise::class)->orderBy('sort_order');
    }
}
