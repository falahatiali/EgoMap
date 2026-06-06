<?php

namespace Tests\Feature\Profile;

use App\Livewire\Missions\Workspace;
use App\Livewire\Profile\ProgramShow;
use App\Livewire\Profile\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\AetherEngine\Database\Seeders\AetherEngineDatabaseSeeder;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class UserAetherProgramHistoryTest extends TestCase
{
    use CreatesSubscriptions;
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
        $this->seed(AetherEngineDatabaseSeeder::class);
    }

    public function test_generate_button_hidden_after_meal_program_exists(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        $component = Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('activeTab', 'nutrition');

        $component
            ->call('openAiMealGenerator')
            ->set('aiAge', 30)
            ->set('aiGender', 'male')
            ->set('aiHeightCm', 180)
            ->set('aiWeightKg', 80)
            ->set('aiTrainingExperience', 'intermediate')
            ->set('aiPrimaryGoal', 'muscle_gain')
            ->set('aiTrainingDaysPerWeek', 4)
            ->set('aiSessionDuration', '45_60')
            ->set('aiPreferredWorkoutTime', 'evening')
            ->set('aiEquipment', 'full_gym')
            ->set('aiDietaryPattern', 'omnivore')
            ->set('aiCookingAbility', 'simple')
            ->set('aiCoachingTone', 'technical')
            ->set('aiMotivationStyle', 'feeling_strong')
            ->call('submitAiQuestionnaire')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment->fresh()])
            ->set('activeTab', 'nutrition')
            ->assertDontSee(__('missions.ai_generate_meal_cta'))
            ->assertSee(__('missions.ai_meal_plan_title'))
            ->assertSee(__('missions.ai_program_view_full'));

        $program = AetherGeneratedProgram::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($program);
        $this->assertSame('meal', $program->applied_target);
        $this->assertSame($enrollment->id, $program->mission_enrollment_id);
    }

    public function test_profile_lists_generated_programs_and_detail_page_loads(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('openAiWorkoutGenerator')
            ->set('aiAge', 30)
            ->set('aiGender', 'male')
            ->set('aiHeightCm', 180)
            ->set('aiWeightKg', 80)
            ->set('aiTrainingExperience', 'intermediate')
            ->set('aiPrimaryGoal', 'muscle_gain')
            ->set('aiTrainingDaysPerWeek', 4)
            ->set('aiSessionDuration', '45_60')
            ->set('aiPreferredWorkoutTime', 'evening')
            ->set('aiEquipment', 'full_gym')
            ->set('aiDietaryPattern', 'omnivore')
            ->set('aiCookingAbility', 'simple')
            ->set('aiCoachingTone', 'technical')
            ->set('aiMotivationStyle', 'feeling_strong')
            ->call('submitAiQuestionnaire')
            ->assertHasNoErrors();

        $program = AetherGeneratedProgram::query()->where('user_id', $user->id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(Show::class)
            ->assertSee(__('profile.programs_title'))
            ->assertSee(route('profile.program.show', ['uuid' => $program->uuid]), false);

        Livewire::actingAs($user)
            ->test(ProgramShow::class, ['uuid' => $program->uuid])
            ->assertSee(__('profile.program_workout_title'))
            ->assertSee(__('profile.program_metabolic_title'));
    }
}
