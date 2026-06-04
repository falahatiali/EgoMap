<?php

namespace Tests\Unit\MissionEngine\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionActivityLog;
use Modules\MissionEngine\Services\MissionEnrollmentFieldService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionEnrollmentFieldServiceTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_merge_deep_merges_field_values_without_dropping_keys(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $service = app(MissionEnrollmentFieldService::class);

        $service->merge($enrollment, [
            'gym_days' => ['sat', 'mon'],
            'equipment_notes' => 'Old note',
        ], $user);

        $enrollment->refresh();

        $service->merge($enrollment, [
            'preferred_gym_time' => '19:30',
            'equipment_notes' => 'Updated note',
        ], $user);

        $enrollment->refresh();

        $this->assertSame(['sat', 'mon'], $enrollment->field_values['gym_days']);
        $this->assertSame('19:30', $enrollment->field_values['preferred_gym_time']);
        $this->assertSame('Updated note', $enrollment->field_values['equipment_notes']);
    }

    public function test_merge_records_field_updated_activity_log(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        app(MissionEnrollmentFieldService::class)->merge($enrollment, [
            'gym_days' => ['wed'],
        ], $user);

        $this->assertDatabaseHas('mission_activity_logs', [
            'enrollment_id' => $enrollment->id,
            'user_id' => $user->id,
            'event_type' => MissionActivityEvent::FieldUpdated->value,
        ]);

        $log = MissionActivityLog::query()
            ->where('enrollment_id', $enrollment->id)
            ->latest('id')
            ->first();

        $this->assertContains('gym_days', $log->payload['keys'] ?? []);
    }

    public function test_merge_touches_last_activity_at(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $enrollment->update(['last_activity_at' => now()->subDay()]);

        app(MissionEnrollmentFieldService::class)->merge($enrollment, [
            'gym_days' => ['fri'],
        ], $user);

        $enrollment->refresh();

        $this->assertTrue($enrollment->last_activity_at->isToday());
    }
}
