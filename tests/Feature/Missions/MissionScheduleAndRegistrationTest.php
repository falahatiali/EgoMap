<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionScheduleAndRegistrationTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_livewire_saves_gym_schedule(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('activeTab', 'schedule')
            ->set('gymDays', ['sat', 'mon', 'wed'])
            ->set('preferredGymTime', '17:30')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertSame(['sat', 'mon', 'wed'], $enrollment->field_values['gym_days']);
        $this->assertSame('17:30', $enrollment->field_values['preferred_gym_time']);
    }

    public function test_toggle_registration_step_persists_progress(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('activeTab', 'registration')
            ->call('toggleRegistrationStep', 'visit_gym')
            ->call('toggleRegistrationStep', 'sign_contract');

        $enrollment->refresh();

        $this->assertTrue($enrollment->field_values['registration_progress']['visit_gym'] ?? false);
        $this->assertTrue($enrollment->field_values['registration_progress']['sign_contract'] ?? false);
        $this->assertFalse($enrollment->field_values['registration_progress']['first_session'] ?? false);
    }

    public function test_schedule_save_writes_field_updated_log(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('gymDays', ['fri'])
            ->call('saveSchedule');

        $this->assertDatabaseHas('mission_activity_logs', [
            'enrollment_id' => $enrollment->id,
            'event_type' => MissionActivityEvent::FieldUpdated->value,
        ]);
    }

    public function test_invalid_tab_url_defaults_to_workout(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'invalid-tab'])
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->assertSet('activeTab', 'workout');
    }
}
