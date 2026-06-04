<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'enrollment_id',
    'session_date',
    'day_key',
    'focus',
    'duration_minutes',
    'notes',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionWorkoutSession extends Model
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
            'session_date' => 'date',
            'duration_minutes' => 'integer',
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
     * @return HasMany<MissionWorkoutExercise, $this>
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(MissionWorkoutExercise::class, 'workout_session_id')->orderBy('sort_order');
    }
}
