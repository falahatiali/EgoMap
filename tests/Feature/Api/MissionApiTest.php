<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Models\MissionEnrollment;
use Tests\TestCase;

class MissionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MissionEngineDatabaseSeeder::class);
    }

    public function test_missions_catalog_is_public_and_lists_gym_mission(): void
    {
        $response = $this->getJson('/api/v1/missions', [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk();

        $missions = collect($response->json('missions'));
        $gym = $missions->firstWhere('slug', GymBodybuildingMissionSeeder::SLUG);

        $this->assertNotNull($gym);
        $this->assertSame('aether', $gym['meta']['engine_module'] ?? null);

        $response
            ->assertJsonStructure([
                'missions' => [
                    ['slug', 'title', 'summary', 'meta' => ['highlights', 'outcomes']],
                ],
                'labels',
            ]);
    }

    public function test_mission_detail_includes_phases_and_fields(): void
    {
        $response = $this->getJson('/api/v1/missions/'.GymBodybuildingMissionSeeder::SLUG, [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('mission.slug', GymBodybuildingMissionSeeder::SLUG)
            ->assertJsonCount(4, 'mission.phases')
            ->assertJsonStructure([
                'mission' => ['description', 'phases', 'capabilities', 'fields'],
            ]);
    }

    public function test_authenticated_user_can_enroll_and_fetch_workspace(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        Sanctum::actingAs($user);

        $enroll = $this->postJson('/api/v1/missions/'.GymBodybuildingMissionSeeder::SLUG.'/enroll');

        $enroll->assertCreated()
            ->assertJsonPath('already_enrolled', false)
            ->assertJsonStructure([
                'enrollment' => ['uuid', 'progress_percent', 'tabs', 'programs', 'field_values'],
            ]);

        $uuid = $enroll->json('enrollment.uuid');

        $this->getJson('/api/v1/mission-enrollments/'.$uuid)
            ->assertOk()
            ->assertJsonPath('enrollment.uuid', $uuid)
            ->assertJsonPath('enrollment.programs.workout', null);

        $this->patchJson('/api/v1/mission-enrollments/'.$uuid.'/fields', [
            'fields' => [
                'gym_days' => ['sat', 'wed'],
                'preferred_gym_time' => '19:30',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('enrollment.field_values.gym_days', ['sat', 'wed']);

        $this->postJson('/api/v1/mission-enrollments/'.$uuid.'/daily-reports', [
            'report_date' => now()->toDateString(),
            'mood_score' => 8,
            'trained_today' => true,
        ])
            ->assertOk()
            ->assertJsonPath('report.mood_score', 8);

        $this->assertSame(1, MissionEnrollment::query()->where('user_id', $user->id)->count());

        $this->postJson('/api/v1/missions/'.GymBodybuildingMissionSeeder::SLUG.'/enroll')
            ->assertOk()
            ->assertJsonPath('already_enrolled', true);
    }

    public function test_user_cannot_access_another_users_enrollment(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('member');
        $intruder = User::factory()->create();
        $intruder->assignRole('member');

        Sanctum::actingAs($owner);
        $uuid = $this->postJson('/api/v1/missions/'.GymBodybuildingMissionSeeder::SLUG.'/enroll')
            ->json('enrollment.uuid');

        Sanctum::actingAs($intruder);
        $this->getJson('/api/v1/mission-enrollments/'.$uuid)->assertForbidden();
    }
}
