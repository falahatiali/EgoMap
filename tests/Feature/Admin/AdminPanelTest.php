<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Livewire\Admin\Dashboard;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect();
    }

    public function test_member_cannot_access_admin(): void
    {
        $member = User::factory()->create();
        $member->assignRole(RoleName::Member->value);

        $this->actingAs($member)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_english_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::SuperAdmin->value);

        $this->withSession(['locale' => 'fa'])
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Mission control', false)
            ->assertSee('Command Center', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('lang="en"', false)
            ->assertDontSee('مرکز فرمان', false);
    }

    public function test_admin_role_can_access_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Mission control', false);
    }
}
