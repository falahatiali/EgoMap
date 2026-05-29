<?php

namespace Tests\Feature\Missions;

use App\Livewire\Missions\Show;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Models\MissionTemplate;
use Tests\TestCase;

class GymBodybuildingUserFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MissionEngineDatabaseSeeder::class);
    }

    public function test_gym_template_is_seeded_and_catalog_is_visible(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $template = MissionTemplate::query()->where('slug', GymBodybuildingMissionSeeder::SLUG)->first();

        $this->assertNotNull($template);
        $this->assertTrue($template->isPublished());
        $this->assertGreaterThanOrEqual(6, $template->fields()->count());

        $this->actingAs($user)
            ->get(route('missions.catalog', ['locale' => 'fa']))
            ->assertOk()
            ->assertSee('باشگاه و بدنسازی', false);
    }

    public function test_member_can_start_gym_mission_and_open_workspace(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $template = MissionTemplate::query()->where('slug', GymBodybuildingMissionSeeder::SLUG)->firstOrFail();

        Livewire::actingAs($user)
            ->test(Show::class, ['template' => $template])
            ->call('startMission')
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('missions.workspace', [
                'locale' => 'fa',
                'enrollment' => $user->missionEnrollments()->firstOrFail()->uuid,
            ]))
            ->assertOk()
            ->assertSee(__('missions.tab_daily', locale: 'fa'), false);
    }
}
