<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\AetherEngine\Database\Seeders\AetherEngineDatabaseSeeder;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class MissionWorkspaceApiTest extends TestCase
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

    public function test_workspace_returns_pro_required_before_calibration_for_free_user(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/mission-enrollments/'.$enrollment->uuid)
            ->assertOk()
            ->assertJsonPath('meta.api_version', '2026-06-12.v1')
            ->assertJsonPath('mission.engine_module', 'aether')
            ->assertJsonPath('workspace.mode', 'locked')
            ->assertJsonPath('engines.aether.status', 'pro_required')
            ->assertJsonPath('engines.aether.programs.workout', null);
    }

    public function test_workspace_returns_locked_aether_state_for_pro_user_before_calibration(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/mission-enrollments/'.$enrollment->uuid)
            ->assertOk()
            ->assertJsonPath('engines.aether.status', 'locked')
            ->assertJsonPath('engines.aether.status_reason', 'calibration_required');
    }

    public function test_calibration_defaults_returns_wizard_prefill(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/defaults')
            ->assertOk()
            ->assertJsonStructure([
                'wizard' => ['primary_goal', 'training_days_per_week', 'equipment'],
                'profile_complete',
                'already_calibrated',
            ])
            ->assertJsonPath('already_calibrated', false);
    }

    public function test_subscribed_user_can_complete_calibration_and_activate_workspace(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/complete', $this->calibrationPayload(['workout']))
            ->assertCreated()
            ->assertJsonPath('activation.status', 'completed')
            ->assertJsonPath('workspace.mode', 'active')
            ->assertJsonPath('engines.aether.status', 'active')
            ->assertJsonStructure([
                'reveal' => ['headline', 'primary_cta', 'phase_timeline'],
                'tools',
            ]);

        $programUuid = $response->json('engines.aether.programs.workout.uuid');
        $this->assertNotNull($programUuid);

        $this->assertDatabaseHas('aether_generated_programs', [
            'uuid' => $programUuid,
            'mission_enrollment_id' => $enrollment->id,
            'applied_target' => 'workout',
        ]);
    }

    public function test_duplicate_calibration_returns_conflict_with_regenerate_link(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);
        Sanctum::actingAs($user);

        $payload = $this->calibrationPayload(['workout']);

        $this->postJson('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/complete', $payload)
            ->assertCreated();

        $this->postJson('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/complete', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'already_calibrated')
            ->assertJsonStructure(['regenerate_api', 'engines']);
    }

    public function test_regenerate_with_force_replaces_program(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();
        $this->createSubscription($user);
        Sanctum::actingAs($user);

        $payload = $this->calibrationPayload(['workout']);

        $this->postJson('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/complete', $payload)
            ->assertCreated();

        $firstCount = AetherGeneratedProgram::query()
            ->where('mission_enrollment_id', $enrollment->id)
            ->count();

        $this->postJson('/api/v1/mission-enrollments/'.$enrollment->uuid.'/calibration/regenerate', [
            ...$payload,
            'force' => true,
        ])->assertOk()
            ->assertJsonPath('engines.aether.status', 'active');

        $this->assertGreaterThan($firstCount, AetherGeneratedProgram::query()
            ->where('mission_enrollment_id', $enrollment->id)
            ->count());
    }

    /**
     * @param  list<string>  $targets
     * @return array<string, mixed>
     */
    private function calibrationPayload(array $targets): array
    {
        return [
            'intent' => [
                'entry_tool_key' => 'task',
            ],
            'targets' => $targets,
            'wizard' => [
                'age' => 28,
                'gender' => 'male',
                'height_cm' => 175,
                'weight_kg' => 75,
                'body_fat_percent' => null,
                'primary_goal' => 'muscle_gain',
                'training_days_per_week' => 4,
                'session_duration' => '45_60',
                'preferred_workout_time' => 'evening',
                'gym_days' => ['mon', 'tue', 'thu', 'sat'],
                'equipment' => 'full_gym',
                'injury_tags' => [],
                'dietary_pattern' => 'omnivore',
                'cooking_ability' => 'simple',
                'coaching_tone' => 'gentle',
                'motivation_style' => 'feeling_strong',
                'training_style' => 'heavy_weights',
            ],
            'commitment' => [
                'confirmed' => true,
            ],
        ];
    }
}
