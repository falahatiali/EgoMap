<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'aether_generated_program_id',
    'check_in_date',
    'weight_kg',
    'body_fat_percent',
    'sleep_quality',
    'sleep_hours',
    'stress_level',
    'energy_level',
    'hunger_level',
    'soreness_level',
    'workout_adherence_percent',
    'nutrition_adherence_percent',
    'feedback',
    'pain_points',
])]
class AetherUserCheckIn extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'weight_kg' => 'decimal:2',
            'body_fat_percent' => 'decimal:1',
            'sleep_hours' => 'decimal:1',
            'sleep_quality' => 'integer',
            'stress_level' => 'integer',
            'energy_level' => 'integer',
            'hunger_level' => 'integer',
            'soreness_level' => 'integer',
            'workout_adherence_percent' => 'integer',
            'nutrition_adherence_percent' => 'integer',
            'pain_points' => 'array',
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
}
