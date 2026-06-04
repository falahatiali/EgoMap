<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MissionEngine\Enums\MealType;

#[Fillable([
    'nutrition_day_id',
    'meal_type',
    'meal_time',
    'meal_calories',
    'notes',
    'sort_order',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionMeal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal_type' => MealType::class,
            'meal_calories' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MissionNutritionDay, $this>
     */
    public function nutritionDay(): BelongsTo
    {
        return $this->belongsTo(MissionNutritionDay::class, 'nutrition_day_id');
    }

    /**
     * @return HasMany<MissionMealItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MissionMealItem::class, 'meal_id')->orderBy('sort_order');
    }
}
