<?php

namespace Modules\MissionEngine\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meal_id',
    'name',
    'quantity',
    'unit',
    'calories',
    'protein_g',
    'sort_order',
])]
class MissionMealItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'calories' => 'integer',
            'protein_g' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MissionMeal, $this>
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(MissionMeal::class, 'meal_id');
    }
}
