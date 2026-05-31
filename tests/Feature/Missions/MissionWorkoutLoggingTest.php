<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Enums\MissionActivityEvent;
use Modules\MissionEngine\Models\MissionWorkoutSession;
use Modules\MissionEngine\Services\MissionWorkoutLogService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionWorkoutLoggingTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_save_session_upserts_same_date_and_replaces_exercises(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $service = app(MissionWorkoutLogService::class);
        $date = now()->toDateString();

        $service->saveSession($enrollment, $user, [
            'session_date' => $date,
            'focus' => 'legs',
            'exercises' => [
                ['name' => 'Squat', 'sets' => [['reps' => 10, 'weight' => 80]]],
            ],
        ]);

        $service->saveSession($enrollment, $user, [
            'session_date' => $date,
            'focus' => 'chest',
            'exercises' => [
                ['name' => 'Bench press', 'sets' => [['reps' => 8, 'weight' => 60]]],
            ],
        ]);

        $this->assertSame(1, MissionWorkoutSession::query()->where('enrollment_id', $enrollment->id)->count());

        $session = MissionWorkoutSession::query()
            ->where('enrollment_id', $enrollment->id)
            ->with('exercises')
            ->first();

        $this->assertSame('chest', $session->focus);
        $this->assertCount(1, $session->exercises);
        $this->assertSame('Bench press', $session->exercises->first()->name);
    }

    public function test_save_session_creates_workout_activity_log(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        app(MissionWorkoutLogService::class)->saveSession($enrollment, $user, [
            'session_date' => now()->toDateString(),
            'exercises' => [
                ['name' => 'Deadlift', 'sets' => [['reps' => 5, 'weight' => 100]]],
            ],
        ]);

        $this->assertDatabaseHas('mission_activity_logs', [
            'enrollment_id' => $enrollment->id,
            'event_type' => MissionActivityEvent::WorkoutLogged->value,
        ]);
    }

    public function test_find_session_for_date_returns_loaded_relations(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $date = now()->toDateString();

        app(MissionWorkoutLogService::class)->saveSession($enrollment, $user, [
            'session_date' => $date,
            'exercises' => [
                ['name' => 'Squat', 'sets' => [['reps' => 10, 'weight' => 80]]],
            ],
        ]);

        $found = app(MissionWorkoutLogService::class)->findSessionForDate($enrollment, $date);

        $this->assertNotNull($found);
        $this->assertTrue($found->relationLoaded('exercises'));
        $this->assertTrue($found->exercises->first()->relationLoaded('sets'));
    }

    public function test_livewire_rejects_workout_without_valid_sets(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('workoutExercises', [
                ['name' => 'Squat', 'notes' => '', 'sets' => [['reps' => '', 'weight' => '', 'notes' => '']]],
            ])
            ->call('saveWorkoutSession')
            ->assertHasErrors('workoutExercises');

        $this->assertSame(0, MissionWorkoutSession::query()->where('enrollment_id', $enrollment->id)->count());
    }

    public function test_livewire_saves_workout_plan_to_field_values(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['enrollment' => $enrollment])
            ->set('workoutPlan', [
                ['day' => 'sat', 'focus' => 'Legs', 'notes' => 'heavy'],
            ])
            ->call('saveWorkoutPlan')
            ->assertHasNoErrors();

        $enrollment->refresh();

        $this->assertSame('Legs', $enrollment->field_values['workout_plan'][0]['focus']['en']);
        $this->assertSame('heavy', $enrollment->field_values['workout_plan'][0]['notes']['en']);
    }

    public function test_workspace_forbidden_for_other_user(): void
    {
        [$owner, $enrollment] = $this->enrollMemberInGymMission();
        [$other] = $this->enrollMemberInGymMission();

        $this->actingAs($other)
            ->get(route('missions.workspace', [
                'locale' => 'fa',
                'enrollment' => $enrollment->uuid,
            ]))
            ->assertForbidden();
    }
}
