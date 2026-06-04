<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Services\Gamification\GamificationSlipHandler;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Enums\GamificationUserPunishmentStatus;
use Modules\GamificationEngine\Models\GamificationPunishment;
use Modules\GamificationEngine\Models\GamificationUserPunishment;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationPunishmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostModePunishmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationEngineDatabaseSeeder::class);
    }

    public function test_slip_returns_suggested_punishments(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 40,
            'coins' => 10,
            'streak_days' => 4,
            'badges' => [],
            'perks' => [],
        ]);

        $result = app(GamificationSlipHandler::class)->record('felt_weak', [
            'user_id' => $user->id,
            'metadata' => ['trigger' => 'felt_weak'],
        ]);

        $this->assertNotEmpty($result['suggested_punishments'] ?? []);
        $this->assertGreaterThanOrEqual(3, count($result['suggested_punishments']));
    }

    public function test_completing_punishment_restores_partial_points(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 20,
            'coins' => 5,
            'streak_days' => 0,
            'badges' => [],
            'perks' => [],
        ]);

        $punishment = GamificationPunishment::query()->where('slug', 'box_breathing_5min')->first();
        $this->assertNotNull($punishment);

        $service = app(GamificationPunishmentService::class);
        $assigned = $service->assign($user, $punishment->id, null, 'felt_weak');

        $outcome = $service->complete($assigned);
        $wallet = GamificationWallet::query()->where('user_id', $user->id)->first();

        $this->assertSame(GamificationUserPunishmentStatus::Completed, $assigned->fresh()->status);
        $this->assertGreaterThanOrEqual(21, $wallet->points);
        $this->assertNotEmpty($outcome['gamification']['applied'] ?? []);
    }

    public function test_panic_used_event_grants_reward(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 0,
            'coins' => 0,
            'badges' => [],
            'perks' => [],
        ]);

        $result = app(GamificationEngine::class)->dispatch(
            GamificationEvent::GhostModePanicUsed->value,
            ['user_id' => $user->id, 'metadata' => []],
        );

        $this->assertSame(5, $result['points_delta']);
        $this->assertSame(1, $result['coins_delta']);
    }

    public function test_physical_cooldown_limits_suggestions(): void
    {
        $user = User::factory()->create();

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 0,
            'coins' => 0,
            'metadata' => [
                'physical_punishments' => [
                    'count_'.now()->toDateString() => 2,
                    'last_at' => now()->toIso8601String(),
                ],
            ],
            'badges' => [],
            'perks' => [],
        ]);

        $suggestions = app(GamificationPunishmentService::class)->suggest('sent_message', $user->id);

        foreach ($suggestions as $item) {
            $this->assertNotSame('physical', $item['type']);
        }
    }
}
