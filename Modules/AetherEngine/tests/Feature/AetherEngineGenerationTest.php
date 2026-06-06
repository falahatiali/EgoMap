<?php

namespace Modules\AetherEngine\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\AetherEngine\Database\Seeders\AetherEngineDatabaseSeeder;
use Modules\AetherEngine\Enums\CoachingTone;
use Modules\AetherEngine\Enums\CookingAbility;
use Modules\AetherEngine\Enums\DietaryPattern;
use Modules\AetherEngine\Enums\EquipmentAccess;
use Modules\AetherEngine\Enums\Gender;
use Modules\AetherEngine\Enums\MotivationStyle;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Enums\ProgramStatus;
use Modules\AetherEngine\Enums\SessionDuration;
use Modules\AetherEngine\Enums\TrainingExperience;
use Modules\AetherEngine\Enums\WorkoutTimePreference;
use Modules\AetherEngine\Models\AetherExercise;
use Modules\AetherEngine\Models\AetherMealTemplate;
use Modules\AetherEngine\Models\AetherProgramExercise;
use Modules\AetherEngine\Models\AetherProgramMeal;
use Modules\AetherEngine\Models\AetherProgramNutritionDay;
use Modules\AetherEngine\Models\AetherProgramScheduleEntry;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;
use Modules\AetherEngine\Models\AetherUserProfile;
use Modules\AetherEngine\Services\AetherEngineService;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\AetherEngine\Services\MetabolicCalculator;
use Modules\AetherEngine\Services\WorkoutGenerator;
use Tests\TestCase;

class AetherEngineGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AetherEngineDatabaseSeeder::class);
    }

    public function test_catalog_is_seeded_with_exercises_and_meals(): void
    {
        $this->assertGreaterThanOrEqual(50, AetherExercise::query()->count());
        $this->assertGreaterThanOrEqual(30, AetherMealTemplate::query()->count());
    }

    public function test_metabolic_calculator_returns_evidence_based_targets(): void
    {
        $profile = $this->makeProfile();

        $targets = app(MetabolicCalculator::class)->calculate($profile);

        $this->assertGreaterThan(1200, $targets->bmr);
        $this->assertGreaterThan($targets->bmr, $targets->tdee);
        $this->assertGreaterThan(80, $targets->proteinGrams);
        $this->assertGreaterThan(40, $targets->carbGrams);
    }

    public function test_workout_generator_respects_knee_injury_filter(): void
    {
        $profile = $this->makeProfile([
            'injuries_limitations' => 'Chronic knee pain when squatting deep',
            'injury_tags' => ['knee'],
        ]);

        $result = app(WorkoutGenerator::class)->generate($profile);

        $exerciseSlugs = collect($result['days'])
            ->flatMap(fn ($day) => collect($day->exercises)->pluck('slug'))
            ->all();

        $this->assertNotContains('barbell-squat', $exerciseSlugs);
        $this->assertCount(4, $result['days']);
    }

    public function test_engine_generates_and_persists_relational_program_graph(): void
    {
        $user = User::factory()->create();
        $profile = $this->makeProfile(['user_id' => $user->id]);

        $program = app(AetherEngineService::class)->generate($profile);

        $this->assertSame(ProgramStatus::Active, $program->status);
        $this->assertSame($user->id, $program->user_id);
        $this->assertNotNull($program->metabolic_target_calories);
        $this->assertNotNull($program->split);
        $this->assertNotEmpty($program->shopping_list_summary);
        $this->assertNotNull($program->coach_title);

        $this->assertGreaterThan(0, AetherProgramWorkoutDay::query()->where('aether_generated_program_id', $program->id)->count());
        $this->assertGreaterThan(0, AetherProgramExercise::query()->count());
        $this->assertSame(7, AetherProgramNutritionDay::query()->where('aether_generated_program_id', $program->id)->count());
        $this->assertGreaterThan(0, AetherProgramMeal::query()->count());
        $this->assertGreaterThan(0, AetherProgramScheduleEntry::query()->where('aether_generated_program_id', $program->id)->count());
        $this->assertFalse(Schema::hasColumn('aether_generated_programs', 'program_data'));
    }

    public function test_regenerating_archives_previous_active_program(): void
    {
        $user = User::factory()->create();
        $profile = $this->makeProfile(['user_id' => $user->id]);

        $first = app(AetherEngineService::class)->generate($profile);
        $second = app(AetherEngineService::class)->generate($profile->fresh());

        $first->refresh();

        $this->assertSame(ProgramStatus::Archived, $first->status);
        $this->assertSame(ProgramStatus::Active, $second->status);
        $this->assertSame(2, $second->version);
    }

    public function test_profile_service_upserts_questionnaire_for_user(): void
    {
        $user = User::factory()->create();
        $answers = collect($this->profileAttributes())
            ->except(['injury_tags', 'questionnaire_completed_at'])
            ->put('injuries_limitations', 'ACL recovery — avoid deep knee flexion')
            ->all();

        $profile = app(AetherProfileService::class)->upsertForUser($user, $answers);

        $this->assertTrue($profile->isQuestionnaireComplete());
        $this->assertContains('knee', $profile->resolvedInjuryTags());
        $this->assertSame($user->id, AetherUserProfile::query()->where('user_id', $user->id)->value('user_id'));
    }

    public function test_vegan_profile_receives_vegan_meals_only(): void
    {
        $profile = $this->makeProfile([
            'dietary_pattern' => DietaryPattern::Vegan,
        ]);

        $program = app(AetherEngineService::class)->generate($profile);
        $mealNames = $program->nutritionDays
            ->flatMap(fn ($day) => $day->meals->pluck('name'))
            ->implode(' ');

        $this->assertStringNotContainsString('Chicken', $mealNames);
        $this->assertStringNotContainsString('Salmon', $mealNames);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeProfile(array $overrides = []): AetherUserProfile
    {
        $userId = $overrides['user_id'] ?? User::factory()->create()->id;

        return AetherUserProfile::query()->create(array_merge($this->profileAttributes(), [
            'user_id' => $userId,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function profileAttributes(): array
    {
        return [
            'age' => 32,
            'gender' => Gender::Male,
            'height_cm' => 178,
            'weight_kg' => 82.5,
            'body_fat_percent' => 18.0,
            'training_experience' => TrainingExperience::Intermediate,
            'primary_goal' => PrimaryGoal::MuscleGain,
            'secondary_goal' => null,
            'target_weight_kg' => 85.0,
            'target_body_fat_percent' => null,
            'stress_level' => 6,
            'sleep_hours' => 7.5,
            'training_days_per_week' => 4,
            'session_duration' => SessionDuration::FortyFiveToSixty,
            'preferred_workout_time' => WorkoutTimePreference::Evening,
            'equipment' => EquipmentAccess::FullGym,
            'injury_tags' => ['knee'],
            'injuries_limitations' => 'Mild knee discomfort on deep squats',
            'dietary_pattern' => DietaryPattern::Omnivore,
            'allergies' => ['shellfish'],
            'cooking_ability' => CookingAbility::Simple,
            'estimated_daily_calories' => null,
            'typical_meals' => 'Coffee, office lunch, home dinner',
            'favorite_exercises' => ['pull-up'],
            'disliked_exercises' => ['burpee'],
            'motivation_style' => MotivationStyle::FeelingStrong,
            'coaching_tone' => CoachingTone::Technical,
            'supplements' => ['creatine', 'whey'],
            'medical_conditions' => null,
            'questionnaire_completed_at' => now(),
        ];
    }
}
