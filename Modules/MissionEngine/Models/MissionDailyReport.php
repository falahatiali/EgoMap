<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'enrollment_id',
    'report_date',
    'body_weight',
    'mood_score',
    'energy_score',
    'sleep_hours',
    'trained_today',
    'nutrition_logged',
    'highlights',
    'challenges',
    'notes',
    'workout_session_id',
    'nutrition_day_id',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionDailyReport extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'body_weight' => 'decimal:2',
            'mood_score' => 'integer',
            'energy_score' => 'integer',
            'sleep_hours' => 'decimal:2',
            'trained_today' => 'boolean',
            'nutrition_logged' => 'boolean',
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
     * @return BelongsTo<MissionWorkoutSession, $this>
     */
    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(MissionWorkoutSession::class, 'workout_session_id');
    }

    /**
     * @return BelongsTo<MissionNutritionDay, $this>
     */
    public function nutritionDay(): BelongsTo
    {
        return $this->belongsTo(MissionNutritionDay::class, 'nutrition_day_id');
    }
}
