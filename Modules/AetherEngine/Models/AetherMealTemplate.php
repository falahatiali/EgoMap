<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\AetherEngine\Enums\MealType;

#[Fillable([
    'slug',
    'name',
    'meal_type',
    'dietary_tags',
    'calories',
    'protein_g',
    'carbs_g',
    'fat_g',
    'ingredients',
    'instructions',
    'prep_time_minutes',
    'is_active',
])]
class AetherMealTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dietary_tags' => 'array',
            'ingredients' => 'array',
            'calories' => 'integer',
            'protein_g' => 'integer',
            'carbs_g' => 'integer',
            'fat_g' => 'integer',
            'prep_time_minutes' => 'integer',
            'is_active' => 'boolean',
            'meal_type' => MealType::class,
        ];
    }
}
