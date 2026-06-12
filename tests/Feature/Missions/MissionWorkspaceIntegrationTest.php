<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Models\MissionDailyReport;
use Modules\MissionEngine\Models\MissionSupplementIntake;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionWorkspaceIntegrationTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_full_day_logging_across_workspace_tabs(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('logDate', $date)
            ->set('gymDays', ['sat'])
            ->set('preferredGymTime', '18:00')
            ->call('saveSchedule')
            ->set('intakeProductName', 'Creatine')
            ->set('intakeAmount', '5')
            ->set('intakeUnit', 'g')
            ->call('logSupplementIntake')
            ->set('activeTab', 'daily')
            ->set('reportMood', 8)
            ->set('reportWeight', 81.2)
            ->set('reportTrained', true)
            ->call('saveDailyReport')
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertSame(['sat'], $enrollment->field_values['gym_days']);
        $this->assertSame('18:00', $enrollment->field_values['preferred_gym_time']);
        $this->assertSame(1, MissionSupplementIntake::query()->where('enrollment_id', $enrollment->id)->count());
        $this->assertSame(1, MissionDailyReport::query()->where('enrollment_id', $enrollment->id)->count());

        $report = MissionDailyReport::query()->where('enrollment_id', $enrollment->id)->first();
        $this->assertTrue($report->trained_today);
        $this->assertSame(8, $report->mood_score);
    }
}
