<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Livewire\Admin\MissionEngine\Templates\Create;
use App\Livewire\Admin\MissionEngine\Templates\Edit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Enums\MissionTemplateStatus;
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

    public function test_admin_can_add_and_save_field_on_template_edit(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $admin->givePermissionTo(Permission::AdminMissionsManage->value);

        $template = MissionTemplate::query()->where('slug', 'gym-bodybuilding')->firstOrFail();
        $initialCount = $template->fields()->count();

        $component = Livewire::actingAs($admin)
            ->test(Edit::class, ['template' => $template])
            ->call('addField')
            ->assertSet('activeTab', 'fields')
            ->assertCount('fieldDrafts', $initialCount + 1);

        $drafts = $component->get('fieldDrafts');
        $index = count($drafts) - 1;
        $newFieldId = (int) $drafts[$index]['id'];

        $component
            ->set("fieldDrafts.{$index}.field_key", 'coach_notes')
            ->set("fieldDrafts.{$index}.label_en", 'Coach notes')
            ->call('saveField', $newFieldId)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mission_template_fields', [
            'id' => $newFieldId,
            'field_key' => 'coach_notes',
        ]);
    }

    public function test_publish_is_blocked_when_template_has_no_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $admin->givePermissionTo(Permission::AdminMissionsManage->value);

        $template = MissionTemplate::query()->make([
            'slug' => 'shell-mission',
            'status' => MissionTemplateStatus::Draft,
            'difficulty' => 'beginner',
            'version' => 1,
            'is_featured' => false,
            'sort_order' => 0,
        ]);
        $template->setTranslation('title', 'en', 'Shell');
        $template->save();

        $component = Livewire::actingAs($admin)
            ->test(Edit::class, ['template' => $template])
            ->set('status', MissionTemplateStatus::Published->value)
            ->call('saveDetails')
            ->assertSet('pageNoticeType', 'danger');

        $this->assertNotEmpty($component->get('lastSaveErrors'));

        $template->refresh();
        $this->assertSame(MissionTemplateStatus::Draft, $template->status);
    }

    public function test_admin_can_publish_complete_template(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $admin->givePermissionTo(Permission::AdminMissionsManage->value);

        $template = MissionTemplate::query()->where('slug', 'gym-bodybuilding')->firstOrFail();
        $template->update(['status' => MissionTemplateStatus::Draft, 'published_at' => null]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['template' => $template->fresh()])
            ->set('status', MissionTemplateStatus::Published->value)
            ->call('saveDetails')
            ->assertHasNoErrors()
            ->assertSet('pageNoticeType', 'success')
            ->assertSet('status', MissionTemplateStatus::Published->value);

        $template->refresh();
        $this->assertSame(MissionTemplateStatus::Published, $template->status);
        $this->assertNotNull($template->published_at);
    }
}
