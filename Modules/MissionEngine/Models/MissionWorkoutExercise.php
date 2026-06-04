<?php

namespace Modules\MissionEngine\Models;

use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workout_session_id', 'name', 'sort_order', 'notes'])]
#[ObservedBy([AssignsUuidObserver::class])]
class MissionWorkoutExercise extends Model
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

    /**
     * @return BelongsTo<MissionWorkoutSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(MissionWorkoutSession::class, 'workout_session_id');
    }

    /**
     * @return HasMany<MissionWorkoutSet, $this>
     */
    public function sets(): HasMany
    {
        return $this->hasMany(MissionWorkoutSet::class, 'workout_exercise_id')->orderBy('set_number');
    }
}
