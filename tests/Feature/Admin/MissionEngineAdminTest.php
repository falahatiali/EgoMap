<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Livewire\Admin\MissionEngine\Templates\Create;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Models\MissionTemplate;
use Tests\TestCase;

class MissionEngineAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MissionEngineDatabaseSeeder::class);
    }

    public function test_guest_cannot_access_mission_engine_admin(): void
    {
        $this->get(route('admin.mission-engine.templates.index'))
            ->assertRedirect();
    }

    public function test_member_cannot_access_mission_engine_admin(): void
    {
        $member = User::factory()->create();
        $member->assignRole(RoleName::Member->value);

        $this->actingAs($member)
            ->get(route('admin.mission-engine.templates.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_mission_templates_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $admin->givePermissionTo(Permission::AdminMissionsManage->value);

        $this->actingAs($admin)
            ->get(route('admin.mission-engine.templates.index'))
            ->assertOk()
            ->assertSee('Mission Engine');
    }

    public function test_admin_can_create_mission_template_via_livewire(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $admin->givePermissionTo(Permission::AdminMissionsManage->value);

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('slug', 'gym-mission')
            ->set('titleEn', 'Gym Mission')
            ->set('summaryEn', 'Rebuild the body.')
            ->call('save')
            ->assertRedirect(route('admin.mission-engine.templates.edit', [
                'template' => MissionTemplate::query()->where('slug', 'gym-mission')->firstOrFail(),
            ]));

        $this->assertDatabaseHas('mission_templates', [
            'slug' => 'gym-mission',
        ]);
    }
}
