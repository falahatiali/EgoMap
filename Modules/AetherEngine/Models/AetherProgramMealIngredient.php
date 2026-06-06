<?php

namespace Modules\AetherEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'aether_program_meal_id',
    'sort_order',
    'ingredient',
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
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(AetherProgramMeal::class, 'aether_program_meal_id');
    }
}
