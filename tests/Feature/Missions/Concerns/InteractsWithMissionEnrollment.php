<?php

namespace Tests\Feature\Missions\Concerns;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Modules\MissionEngine\Database\Seeders\GymBodybuildingMissionSeeder;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionEnrollmentService;

trait InteractsWithMissionEnrollment
{
    protected function seedMissionEngine(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(MissionEngineDatabaseSeeder::class);
    }

    /**
     * @return array{0: User, 1: MissionEnrollment}
     */
    protected function enrollMemberInGymMission(?User $user = null): array
    {
        $user ??= User::factory()->create();
        $user->assignRole('member');

        $template = MissionTemplate::query()
            ->where('slug', GymBodybuildingMissionSeeder::SLUG)
            ->firstOrFail();

        $enrollment = app(MissionEnrollmentService::class)->enroll($user, $template);

        return [$user, $enrollment];
    }
}
