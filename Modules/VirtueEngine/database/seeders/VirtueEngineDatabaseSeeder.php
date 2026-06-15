<?php

namespace Modules\VirtueEngine\Database\Seeders;

use Illuminate\Database\Seeder;

class VirtueEngineDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(VirtueHabitSeeder::class);
    }
}
