<?php

namespace Modules\AetherEngine\Models;

use App\Models\User;
use App\Observers\AssignsUuidObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AetherEngine\Enums\BodyBuild;
use Modules\AetherEngine\Enums\BodyGoal;
use Modules\AetherEngine\Enums\CoachingTone;
use Modules\AetherEngine\Enums\CookingAbility;
use Modules\AetherEngine\Enums\DietaryPattern;
use Modules\AetherEngine\Enums\EquipmentAccess;
use Modules\AetherEngine\Enums\Gender;
use Modules\AetherEngine\Enums\GymConfidence;
use Modules\AetherEngine\Enums\MotivationStyle;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Enums\SessionDuration;
use Modules\AetherEngine\Enums\TrainingExperience;
use Modules\AetherEngine\Enums\WorkoutTimePreference;

#[Fillable([
    'user_id',
    'age',
    'gender',
    'height_cm',
    'weight_kg',
    'body_fat_percent',
    'training_experience',
    'primary_goal',
    'current_body_build',
    'target_body_goal',
    'gym_confidence',
    'secondary_goal',
    'target_weight_kg',
    'target_body_fat_percent',
    'stress_level',
    'sleep_hours',
    'training_days_per_week',
    'session_duration',
    'preferred_workout_time',
    'equipment',
    'injury_tags',
    'injuries_limitations',
    'dietary_pattern',
    'allergies',
    'cooking_ability',
    'estimated_daily_calories',
    'typical_meals',
    'favorite_exercises',
    'disliked_exercises',
    'motivation_style',
    'coaching_tone',
    'supplements',
    'medical_conditions',
    'metadata',
    'questionnaire_completed_at',
])]
#[ObservedBy([AssignsUuidObserver::class])]
class AetherUserProfile extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'decimal:2',
            'body_fat_percent' => 'decimal:1',
            'target_weight_kg' => 'decimal:2',
            'target_body_fat_percent' => 'decimal:1',
            'stress_level' => 'integer',
            'sleep_hours' => 'decimal:1',
            'training_days_per_week' => 'integer',
            'estimated_daily_calories' => 'integer',
            'injury_tags' => 'array',
            'allergies' => 'array',
            'favorite_exercises' => 'array',
            'disliked_exercises' => 'array',
            'supplements' => 'array',
            'metadata' => 'array',
            'questionnaire_completed_at' => 'datetime',
            'gender' => Gender::class,
            'training_experience' => TrainingExperience::class,
            'primary_goal' => PrimaryGoal::class,
            'current_body_build' => BodyBuild::class,
            'target_body_goal' => BodyGoal::class,
            'gym_confidence' => GymConfidence::class,
            'secondary_goal' => PrimaryGoal::class,
            'session_duration' => SessionDuration::class,
            'preferred_workout_time' => WorkoutTimePreference::class,
            'equipment' => EquipmentAccess::class,
            'dietary_pattern' => DietaryPattern::class,
            'cooking_ability' => CookingAbility::class,
            'motivation_style' => MotivationStyle::class,
            'coaching_tone' => CoachingTone::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedPrograms(): HasMany
    {
        return $this->hasMany(AetherGeneratedProgram::class);
    }

    public function isQuestionnaireComplete(): bool
    {
        return $this->questionnaire_completed_at !== null;
    }

    /**
     * @return array<int, string>
     */
    public function resolvedInjuryTags(): array
    {
        return $this->injury_tags ?? [];
    }
}
