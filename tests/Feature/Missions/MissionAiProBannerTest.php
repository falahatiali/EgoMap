<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class MissionAiProBannerTest extends TestCase
{
    use CreatesSubscriptions;
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_free_member_sees_pricing_upgrade_not_coming_soon(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->assertSee(__('missions.pro_upgrade_cta'))
            ->assertDontSee('coming soon', false);
    }

    public function test_subscribed_member_sees_generate_workout_button(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->assertSee(__('missions.ai_generate_workout_cta'))
            ->assertSee(__('missions.ai_workout_pro_hint'))
            ->assertDontSee(__('missions.pro_upgrade_cta'));
    }

    public function test_subscribed_member_opens_ai_questionnaire_modal(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->call('openAiWorkoutGenerator')
            ->assertSet('showAiQuestionnaire', true)
            ->assertSet('aiQuestionnaireTarget', 'workout')
            ->assertSee(__('missions.ai_wizard_title_workout'));
    }
}
