<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AetherEngine\Data\ProgramCoachNarrative;
use Modules\AetherEngine\Enums\ProgramStatus;
use Modules\AetherEngine\Enums\WorkoutSplit;
use Modules\MissionEngine\Models\MissionEnrollment;

#[Fillable([
    'user_id',
    'aether_user_profile_id',
    'version',
    'week_number',
    'status',
    'applied_target',
    'mission_enrollment_id',
    'split',
    'shopping_list_summary',
    'metabolic_bmr',
    'metabolic_tdee',
    'metabolic_target_calories',
    'metabolic_protein_grams',
    'metabolic_fat_grams',
    'metabolic_carb_grams',
    'metabolic_protein_g_per_kg',
    'metabolic_activity_multiplier',
    'coach_title',
    'coach_week_focus',
    'coach_mindset_focus',
    'coach_habit_stack',
    'coach_recovery_strategy',
    'coach_supplement_advice',
    'coach_disclaimer',
    'starts_at',
    'ends_at',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class AetherGeneratedProgram extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'week_number' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => ProgramStatus::class,
            'split' => WorkoutSplit::class,
            'metabolic_bmr' => 'integer',
            'metabolic_tdee' => 'integer',
            'metabolic_target_calories' => 'integer',
            'metabolic_protein_grams' => 'integer',
            'metabolic_fat_grams' => 'integer',
            'metabolic_carb_grams' => 'integer',
            'metabolic_protein_g_per_kg' => 'float',
            'metabolic_activity_multiplier' => 'float',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithProgramGraph(Builder $query): void
    {
        $query->with([
            'scheduleEntries',
            'workoutDays.exercises',
            'nutritionDays.meals.ingredients',
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(AetherUserProfile::class, 'aether_user_profile_id');
    }

    public function missionEnrollment(): BelongsTo
    {
        return $this->belongsTo(MissionEnrollment::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(AetherProgramScheduleEntry::class)->orderBy('iso_weekday');
    }

    public function workoutDays(): HasMany
    {
        return $this->hasMany(AetherProgramWorkoutDay::class)->orderBy('day_index');
    }

    public function nutritionDays(): HasMany
    {
        return $this->hasMany(AetherProgramNutritionDay::class)->orderBy('day_index');
    }

    /**
     * @return array<string, int|float|null>
     */
    public function metabolicSummary(): array
    {
        return [
            'bmr' => $this->metabolic_bmr,
            'tdee' => $this->metabolic_tdee,
            'target_calories' => $this->metabolic_target_calories,
            'protein_grams' => $this->metabolic_protein_grams,
            'fat_grams' => $this->metabolic_fat_grams,
            'carb_grams' => $this->metabolic_carb_grams,
            'protein_g_per_kg' => $this->metabolic_protein_g_per_kg,
            'activity_multiplier' => $this->metabolic_activity_multiplier,
        ];
    }

    public function coachNarrative(): ProgramCoachNarrative
    {
        return new ProgramCoachNarrative(
            title: $this->coach_title,
            weekFocus: $this->coach_week_focus,
            mindsetFocus: $this->coach_mindset_focus,
            habitStack: $this->coach_habit_stack,
            recoveryStrategy: $this->coach_recovery_strategy,
            supplementAdvice: $this->coach_supplement_advice,
            disclaimer: $this->coach_disclaimer,
        );
    }
}
