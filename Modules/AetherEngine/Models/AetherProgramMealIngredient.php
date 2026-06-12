<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'aether_program_meal_id',
    'sort_order',
    'name',
    'quantity',
    'unit',
    'calories',
    'protein_g',
    'carbs_g',
    'fat_g',
    'category',
    'is_optional',
])]
class AetherProgramMealIngredient extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'quantity' => 'decimal:2',
            'calories' => 'integer',
            'protein_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'is_optional' => 'boolean',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(AetherProgramMeal::class, 'aether_program_meal_id');
    }
}
