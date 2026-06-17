<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_can_run_twice_without_duplicate_users(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@egomap.test')->count());
        $this->assertSame(1, User::query()->where('email', 'pro@egomap.test')->count());
        $this->assertSame(1, User::query()->where('email', 'member@egomap.test')->count());
    }
}
