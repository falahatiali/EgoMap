<?php

namespace Modules\AetherEngine\Database\Seeders;

use Illuminate\Database\Seeder;

class AetherEngineDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AetherExerciseSeeder::class,
            AetherMealTemplateSeeder::class,
            AetherPromptTemplateSeeder::class,
        ]);
    }
}
