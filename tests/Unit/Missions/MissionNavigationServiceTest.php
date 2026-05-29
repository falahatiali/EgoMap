<?php

namespace Tests\Unit\Missions;

use App\Models\User;
use App\Services\Missions\MissionNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionEnrollmentService;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionNavigationServiceTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_guest_navigation_points_to_catalog(): void
    {
        $nav = app(MissionNavigationService::class)->forUser(null, 'fa');

        $this->assertStringContainsString('/fa/missions', $nav['href']);
        $this->assertSame(0, $nav['active_count']);
    }

    public function test_single_active_mission_links_to_workspace(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $nav = app(MissionNavigationService::class)->forUser($user, 'fa');

        $this->assertSame(1, $nav['active_count']);
        $this->assertStringContainsString($enrollment->uuid, $nav['href']);
        $this->assertStringContainsString('/missions/active/', $nav['href']);
    }

    public function test_multiple_active_missions_link_to_profile_anchor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $template = MissionTemplate::query()->where('slug', GymBodybuildingMissionSeeder::SLUG)->firstOrFail();
        $service = app(MissionEnrollmentService::class);

        $service->enroll($user, $template);
        $service->enroll($user, $template);

        $nav = app(MissionNavigationService::class)->forUser($user, 'fa');

        $this->assertGreaterThanOrEqual(2, $nav['active_count']);
        $this->assertStringContainsString('#missions', $nav['href']);
    }
}
