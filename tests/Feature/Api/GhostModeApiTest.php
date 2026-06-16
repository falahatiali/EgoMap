<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\Quiz\ApiQuizGuestTokenService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Tests\TestCase;

class GhostModeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(GamificationEngineDatabaseSeeder::class);
    }

    public function test_guest_can_bootstrap_ghost_mode_and_receive_guest_token(): void
    {
        $response = $this->getJson('/api/v1/ghost-mode', [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('timer.mode', 'setup')
            ->assertJsonPath('is_authenticated', false)
            ->assertJsonStructure([
                'guest_token',
                'copy' => ['page_title', 'setup_title', 'start_protocol'],
                'timer' => ['presets', 'recommended_days'],
                'wallet',
            ])
            ->assertJsonMissingPath('badge_catalog')
            ->assertJsonMissingPath('shop_items');
    }

    public function test_guest_can_start_protocol_with_guest_token_header(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $bootstrap = $this->getJson('/api/v1/ghost-mode');
        $guestToken = $bootstrap->json('guest_token');

        $response = $this->postJson('/api/v1/ghost-mode/protocol', [
            'duration_days' => 30,
        ], [
            ApiQuizGuestTokenService::HEADER => $guestToken,
        ]);

        $response->assertCreated()
            ->assertJsonPath('timer.mode', 'active')
            ->assertJsonPath('timer.duration_days', 30)
            ->assertJsonMissing(['guest_token']);

        $this->assertDatabaseHas('no_contact_protocols', [
            'duration_days' => 30,
            'status' => 'active',
            'user_id' => null,
        ]);
    }

    public function test_start_protocol_returns_fallback_truth_flashes_without_blocking_on_ai(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.key' => 'sk-test']);

        $bootstrap = $this->getJson('/api/v1/ghost-mode');
        $guestToken = $bootstrap->json('guest_token');

        $startedAt = microtime(true);

        $response = $this->postJson('/api/v1/ghost-mode/protocol', [
            'duration_days' => 30,
        ], [
            ApiQuizGuestTokenService::HEADER => $guestToken,
        ]);

        $elapsedSeconds = microtime(true) - $startedAt;

        $response->assertCreated()
            ->assertJsonPath('timer.mode', 'active')
            ->assertJsonPath('timer.duration_days', 30)
            ->assertJsonStructure(['truth_flashes', 'gamification_events']);

        $this->assertNotEmpty($response->json('truth_flashes'));
        $this->assertLessThan(3, $elapsedSeconds, 'Ghost Mode activation should not wait on AI truth flashes.');
    }

    public function test_authenticated_user_can_start_protocol_with_bearer_token(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/ghost-mode/protocol', [
            'duration_days' => 90,
        ]);

        $response->assertCreated()
            ->assertJsonPath('timer.mode', 'active')
            ->assertJsonPath('is_authenticated', true)
            ->assertJsonMissing(['guest_token']);

        $this->assertDatabaseHas('no_contact_protocols', [
            'user_id' => $user->id,
            'duration_days' => 90,
            'status' => 'active',
        ]);
    }

    public function test_bootstrap_returns_active_timer_for_existing_protocol(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/ghost-mode/protocol', ['duration_days' => 60])
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/ghost-mode')
            ->assertOk()
            ->assertJsonPath('timer.mode', 'active')
            ->assertJsonPath('timer.duration_days', 60)
            ->assertJsonStructure(['truth_flashes', 'wallet'])
            ->assertJsonMissingPath('blackhole_progress');
    }
}
