<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_can_view_free_report_sections_but_not_premium_traps(): void
    {
        $member = User::factory()->create();
        $member->assignRole(RoleName::Member->value);

        $this->assertTrue($member->can(Permission::ReportsViewFree->value));
        $this->assertTrue($member->can(Permission::ReportsSectionBlindSpot->value));
        $this->assertFalse($member->can(Permission::ReportsViewPremium->value));
        $this->assertFalse($member->can(Permission::ReportsSectionTraps->value));
    }

    public function test_pro_user_can_view_premium_sections(): void
    {
        $pro = User::factory()->create();
        $pro->assignRole(RoleName::Pro->value);

        $this->assertTrue($pro->can(Permission::ReportsViewPremium->value));
        $this->assertTrue($pro->can(Permission::ReportsSectionTraps->value));
        $this->assertTrue($pro->can(Permission::ReportsSectionRoadmap->value));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::SuperAdmin->value);

        foreach (Permission::values() as $permission) {
            $this->assertTrue($admin->can($permission), "Missing permission: {$permission}");
        }
    }

    public function test_guest_cannot_access_premium_permission(): void
    {
        $this->assertFalse(auth()->check());
        $this->assertFalse(auth()->user()?->can(Permission::ReportsViewPremium->value) ?? false);
    }
}
