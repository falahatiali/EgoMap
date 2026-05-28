<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Livewire\Admin\Quizzes\Edit as QuizzesEdit;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\MbtiQuizSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MbtiQuizSeeder::class);
    }

    public function test_super_admin_can_list_and_update_users(): void
    {
        $super = User::factory()->create();
        $super->assignRole(RoleName::SuperAdmin->value);

        $target = User::factory()->create(['name' => 'Target Member']);
        $target->assignRole(RoleName::Member->value);

        $this->actingAs($super)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Target Member', false);

        Livewire::actingAs($super)
            ->test(UsersEdit::class, ['user' => $target])
            ->set('name', 'Renamed User')
            ->set('selectedRoles', [RoleName::Pro->value])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed User', $target->fresh()->name);
        $this->assertTrue($target->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_admin_role_cannot_access_roles_without_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_super_admin_can_update_quiz_metadata(): void
    {
        $super = User::factory()->create();
        $super->assignRole(RoleName::SuperAdmin->value);

        $quiz = Quiz::query()->firstOrFail();

        Livewire::actingAs($super)
            ->test(QuizzesEdit::class, ['quiz' => $quiz])
            ->set('nameEn', 'Updated Quiz Title')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated Quiz Title', $quiz->fresh()->getTranslation('name', 'en', true));
    }

    public function test_member_cannot_access_user_management(): void
    {
        $member = User::factory()->create();
        $member->assignRole(RoleName::Member->value);

        Livewire::actingAs($member)
            ->test(UsersIndex::class)
            ->assertForbidden();
    }
}
