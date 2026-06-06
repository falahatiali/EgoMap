<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'aether_generated_program_id',
    'day_index',
    'total_calories',
    'total_protein',
    'total_carbs',
    'total_fat',
    'tip',
])]
class AetherProgramNutritionDay extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_index' => 'integer',
            'total_calories' => 'integer',
            'total_protein' => 'integer',
            'total_carbs' => 'integer',
            'total_fat' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AetherGeneratedProgram::class, 'aether_generated_program_id');
    }

    public function meals(): HasMany
    {
        return $this->hasMany(AetherProgramMeal::class)->orderBy('sort_order');
    }
}
