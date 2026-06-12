<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionDailyReport;
use Modules\MissionEngine\Services\MissionDailyReportService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionDailyReportLoggingTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_save_daily_report_upserts_same_date(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $service = app(MissionDailyReportService::class);
        $date = now()->toDateString();

        $service->save($enrollment, $user, [
            'report_date' => $date,
            'mood_score' => 6,
        ]);

        $service->save($enrollment, $user, [
            'report_date' => $date,
            'mood_score' => 9,
            'energy_score' => 8,
        ]);

        $this->assertSame(1, MissionDailyReport::query()->where('enrollment_id', $enrollment->id)->count());
        $this->assertSame(9, MissionDailyReport::query()->first()->mood_score);
    }

    public function test_save_daily_report_with_weight_creates_measurement(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        app(MissionDailyReportService::class)->save($enrollment, $user, [
            'report_date' => now()->toDateString(),
            'body_weight' => 82.5,
        ]);

        $this->assertDatabaseHas('mission_measurements', [
            'enrollment_id' => $enrollment->id,
            'metric_key' => 'weight',
            'value' => 82.5,
        ]);
    }

    public function test_save_daily_report_creates_activity_log(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        app(MissionDailyReportService::class)->save($enrollment, $user, [
            'report_date' => now()->toDateString(),
            'highlights' => 'Good session',
        ]);

        $this->assertDatabaseHas('mission_activity_logs', [
            'enrollment_id' => $enrollment->id,
            'event_type' => MissionActivityEvent::DailyReportSaved->value,
        ]);
    }

    public function test_livewire_saves_daily_report_without_mission_workout_tables(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $date = now()->toDateString();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('logDate', $date)
            ->set('activeTab', 'daily')
            ->set('reportMood', 8)
            ->set('reportWeight', 81.2)
            ->set('reportNutritionLogged', true)
            ->call('saveDailyReport')
            ->assertHasNoErrors();

        $report = MissionDailyReport::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('report_date', $date)
            ->first();

        $this->assertNotNull($report);
        $this->assertTrue($report->nutrition_logged);
        $this->assertSame(8, $report->mood_score);
    }
}
