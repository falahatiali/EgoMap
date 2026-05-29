<?php

namespace Tests\Feature\Missions;

use App\Models\User;
use App\Services\Missions\MissionNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Missions\Concerns\InteractsWithMissionEnrollment;
use Tests\TestCase;

class MissionNavigationTest extends TestCase
{
    use InteractsWithMissionEnrollment;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMissionEngine();
    }

    public function test_navigation_points_to_catalog_when_no_active_missions(): void
    {
        $user = User::factory()->create();

        $nav = app(MissionNavigationService::class)->forUser($user, 'fa');

        $this->assertSame(0, $nav['active_count']);
        $this->assertStringContainsString('/fa/missions', $nav['href']);
        $this->assertSame($nav['href'], $nav['catalog_href']);
    }

    public function test_navigation_points_to_workspace_when_one_active_mission(): void
    {
        [$user, $enrollment] = $this->enrollMemberInGymMission();

        $nav = app(MissionNavigationService::class)->forUser($user, 'fa');

        $this->assertSame(1, $nav['active_count']);
        $this->assertStringContainsString($enrollment->uuid, $nav['href']);
        $this->assertStringContainsString('/fa/missions/active/', $nav['href']);
    }

    public function test_profile_page_shows_missions_section_for_authenticated_user(): void
    {
        [$user] = $this->enrollMemberInGymMission();

        $this->actingAs($user)
            ->get(route('profile', ['locale' => 'fa']))
            ->assertOk()
            ->assertSee(__('missions.profile_missions'), false)
            ->assertSee(__('nav.my_missions'), false)
            ->assertSee('id="missions"', false);
    }
}
