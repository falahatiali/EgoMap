<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@egomap.test',
        ]);
        $admin->assignRole('super-admin');

        $pro = User::factory()->create([
            'name' => 'Pro User',
            'email' => 'pro@egomap.test',
        ]);
        $pro->assignRole('pro');

        $member = User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@egomap.test',
        ]);
        $member->assignRole('member');

        $this->call(QuizSeeder::class);
        $this->call(MbtiQuizSeeder::class);
        $this->call(RebootProtocolQuizSeeder::class);
    }
}
