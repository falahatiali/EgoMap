<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\AetherEngine\Database\Seeders\AetherEngineDatabaseSeeder;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Support\AetherWorkoutWizardSteps;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class MissionAetherGenerationTest extends TestCase
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

    public function test_subscribed_member_can_generate_and_apply_workout_plan(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('openAiWorkoutGenerator')
            ->set('aiCurrentBodyBuild', 'average')
            ->set('aiTargetBodyGoal', 'athletic')
            ->set('aiGymConfidence', 'comfortable_guidance')
            ->set('aiTrainingDaysPerWeek', 4)
            ->set('aiSessionDuration', '45_60')
            ->set('aiPrimaryGoal', 'muscle_gain')
            ->set('aiEquipment', 'full_gym')
            ->set('aiTrainingStyle', 'heavy_weights')
            ->set('aiMotivationStyle', 'feeling_strong')
            ->call('submitAiQuestionnaire')
            ->assertSet('showAiQuestionnaire', false)
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertEmpty($enrollment->field_values['workout_plan'] ?? []);
        $this->assertSame(1, AetherGeneratedProgram::query()->where('user_id', $user->id)->count());

        $program = AetherGeneratedProgram::query()->where('user_id', $user->id)->first();
        $this->assertSame('workout', $program->applied_target);
        $this->assertSame($enrollment->id, $program->mission_enrollment_id);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment->fresh()])
            ->assertDontSee(__('missions.ai_generate_workout_cta'))
            ->assertSee(__('missions.ai_program_view_full'));
    }

    public function test_wizard_step_navigation_preserves_values_on_submit(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        $component = Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('openAiWorkoutGenerator')
            ->call('aiWizardNext')
            ->call('selectAiAgeRange', '30_39')
            ->call('aiWizardNext')
            ->call('aiWizardNext')
            ->call('aiWizardNext')
            ->set('aiCurrentBodyBuild', 'stocky')
            ->call('aiWizardNext')
            ->set('aiTargetBodyGoal', 'defined')
            ->call('aiWizardNext')
            ->set('aiPrimaryGoal', 'fat_loss')
            ->call('aiWizardNext')
            ->set('aiGymConfidence', 'lost_unsure')
            ->call('aiWizardNext')
            ->call('selectAiTrainingDays', 3)
            ->call('aiWizardNext')
            ->set('aiSessionDuration', '30_45')
            ->call('aiWizardNext')
            ->set('aiEquipment', 'home_gym')
            ->call('aiWizardNext')
            ->call('toggleAiInjury', 'knee')
            ->call('aiWizardNext')
            ->set('aiTrainingStyle', 'hiit')
            ->call('aiWizardNext')
            ->set('aiMotivationStyle', 'aesthetics')
            ->call('aiWizardNext')
            ->assertSet('aiWizardStep', AetherWorkoutWizardSteps::count())
            ->call('submitAiQuestionnaire')
            ->assertHasNoErrors()
            ->assertSet('showAiQuestionnaire', false);
    }

    public function test_capitalized_enum_values_are_normalized_before_validation(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('openAiWorkoutGenerator')
            ->set('aiCurrentBodyBuild', 'average')
            ->set('aiTargetBodyGoal', 'athletic')
            ->set('aiGymConfidence', 'confident_plan')
            ->set('aiTrainingDaysPerWeek', 4)
            ->set('aiSessionDuration', '45_60')
            ->set('aiPrimaryGoal', 'muscle_gain')
            ->set('aiEquipment', 'full_gym')
            ->set('aiTrainingStyle', 'Heavy_Weights')
            ->set('aiMotivationStyle', 'feeling_strong')
            ->call('submitAiQuestionnaire')
            ->assertHasNoErrors();
    }
}
