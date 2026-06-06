<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AetherEngine\Enums\MealType;

#[Fillable([
    'aether_program_nutrition_day_id',
    'sort_order',
    'meal_type',
    'name',
    'calories',
    'protein_grams',
    'carb_grams',
    'fat_grams',
    'instructions',
    'prep_minutes',
])]
class AetherProgramMeal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'meal_type' => MealType::class,
            'calories' => 'integer',
            'protein_grams' => 'integer',
            'carb_grams' => 'integer',
            'fat_grams' => 'integer',
            'prep_minutes' => 'integer',
        ];
    }

    public function nutritionDay(): BelongsTo
    {
        return $this->belongsTo(AetherProgramNutritionDay::class, 'aether_program_nutrition_day_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(AetherProgramMealIngredient::class)->orderBy('sort_order');
    }
}
