<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MissionEngine\Enums\CaloriesStatus;

#[Fillable([
    'enrollment_id',
    'log_date',
    'total_calories',
    'calories_status',
    'meal_quality_score',
    'day_notes',
    'ai_analysis',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionNutritionDay extends Model
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'total_calories' => 'integer',
            'calories_status' => CaloriesStatus::class,
            'meal_quality_score' => 'integer',
            'ai_analysis' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MissionEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(MissionEnrollment::class, 'enrollment_id');
    }

    /**
     * @return HasMany<MissionMeal, $this>
     */
    public function meals(): HasMany
    {
        return $this->hasMany(MissionMeal::class, 'nutrition_day_id')->orderBy('sort_order');
    }
}
