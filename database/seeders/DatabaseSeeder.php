<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AetherEngine\Database\Seeders\AetherEngineDatabaseSeeder;
use Modules\CommunityEngine\Database\Seeders\CommunityEngineDatabaseSeeder;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\MissionEngine\Database\Seeders\MissionEngineDatabaseSeeder;
use Modules\VirtueEngine\Database\Seeders\VirtueEngineDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(MissionEngineDatabaseSeeder::class);
        $this->call(GamificationEngineDatabaseSeeder::class);
        $this->call(AetherEngineDatabaseSeeder::class);
        $this->call(VirtueEngineDatabaseSeeder::class);
        $this->call(CommunityEngineDatabaseSeeder::class);

        $this->seedDemoUser('Admin User', 'admin@egomap.test', RoleName::SuperAdmin);
        $this->seedDemoUser('Pro User', 'pro@egomap.test', RoleName::Pro);
        $this->seedDemoUser('Member User', 'member@egomap.test', RoleName::Member);

        $this->call(QuizSeeder::class);
        $this->call(MbtiQuizSeeder::class);
        $this->call(RebootProtocolQuizSeeder::class);
    }

    private function seedDemoUser(string $name, string $email, RoleName $role): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        if (! $user->hasRole($role->value)) {
            $user->assignRole($role->value);
        }
    }
}
