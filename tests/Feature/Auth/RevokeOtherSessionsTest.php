<?php

namespace Tests\Feature\Auth;

use App\Livewire\Profile\Show;
use App\Models\User;
use App\Services\Auth\UserSessionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RevokeOtherSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_revoke_other_sessions_with_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'remember_token' => 'old-remember-token',
        ]);

        $this->insertSession('other-device-session', $user->id);

        Livewire::actingAs($user)
            ->test(Show::class)
            ->set('revokePassword', 'password123')
            ->call('revokeOtherSessions')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sessions', [
            'id' => 'other-device-session',
        ]);

        $user->refresh();

        $this->assertNotSame('old-remember-token', $user->remember_token);
    }

    public function test_revoke_other_sessions_requires_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $this->insertSession('other-device-session', $user->id);

        Livewire::actingAs($user)
            ->test(Show::class)
            ->set('revokePassword', 'wrong-password')
            ->call('revokeOtherSessions')
            ->assertHasErrors(['revokePassword']);

        $this->assertDatabaseHas('sessions', [
            'id' => 'other-device-session',
        ]);
    }

    public function test_user_session_service_revokes_only_other_sessions(): void
    {
        $user = User::factory()->create();

        $this->insertSession('keep-session', $user->id);
        $this->insertSession('remove-session', $user->id);

        $deleted = app(UserSessionService::class)->revokeOtherSessions($user, 'keep-session');

        $this->assertSame(1, $deleted);
        $this->assertDatabaseHas('sessions', ['id' => 'keep-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'remove-session']);
    }

    private function insertSession(string $id, int $userId): void
    {
        DB::table(config('session.table', 'sessions'))->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('[]'),
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
