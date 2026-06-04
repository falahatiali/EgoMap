<?php

namespace Modules\MissionEngine\Database\Seeders;

use Illuminate\Database\Seeder;

class MissionEngineDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MissionCapabilityTypeSeeder::class,
            MissionCategorySeeder::class,
            GymBodybuildingMissionSeeder::class,
        ]);
    }
}
