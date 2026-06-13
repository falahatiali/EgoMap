<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
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

    public function test_missions_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/missions')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_missions_catalog(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/missions', [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('templates.0.slug', GymBodybuildingMissionSeeder::SLUG)
            ->assertJsonPath('active_enrollments', [])
            ->assertJsonStructure([
                'labels' => ['my_missions_title', 'browse_missions'],
                'templates' => [['slug', 'title', 'summary', 'estimated_days']],
            ]);
    }

    public function test_user_can_view_mission_template_and_enroll(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $slug = GymBodybuildingMissionSeeder::SLUG;

        $this->getJson("/api/v1/missions/{$slug}")
            ->assertOk()
            ->assertJsonPath('template.slug', $slug)
            ->assertJsonStructure(['template' => ['phases', 'capabilities', 'description']]);

        $enroll = $this->postJson("/api/v1/missions/{$slug}/enroll")
            ->assertCreated()
            ->assertJsonPath('enrollment.status', 'active')
            ->assertJsonStructure([
                'enrollment' => ['uuid', 'title', 'progress_percent'],
                'workspace' => ['meta', 'mission', 'enrollment', 'engines', 'workspace', 'tools'],
            ]);

        $uuid = $enroll->json('enrollment.uuid');

        $this->getJson('/api/v1/missions')
            ->assertOk()
            ->assertJsonCount(1, 'active_enrollments');

        $this->getJson("/api/v1/missions/enrollments/{$uuid}")
            ->assertOk()
            ->assertJsonPath('enrollment.uuid', $uuid)
            ->assertJsonPath('mission.engine_module', 'aether')
            ->assertJsonPath('workspace.mode', 'locked')
            ->assertJsonPath('engines.aether.status', 'pro_required')
            ->assertJsonPath('engines.aether.programs.workout', null)
            ->assertJsonStructure(['tools']);

        $this->assertDatabaseHas('mission_enrollments', [
            'user_id' => $user->id,
            'uuid' => $uuid,
        ]);
    }

    public function test_enroll_is_idempotent_for_active_mission(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $template = MissionTemplate::query()->where('slug', GymBodybuildingMissionSeeder::SLUG)->firstOrFail();

        $this->postJson('/api/v1/missions/gym-bodybuilding/enroll')->assertCreated();
        $this->postJson('/api/v1/missions/gym-bodybuilding/enroll')
            ->assertOk()
            ->assertJsonPath('enrollment.status', 'active');

        $this->assertSame(
            1,
            MissionEnrollment::query()->where('user_id', $user->id)->where('template_id', $template->id)->count(),
        );
    }
}
