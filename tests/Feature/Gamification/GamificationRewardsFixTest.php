<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Services\Gamification\GamificationSlipHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Tests\TestCase;

class GamificationRewardsFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationEngineDatabaseSeeder::class);
    }

    public function test_daily_login_applies_streak_milestone_when_streak_reaches_seven(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'streak_days' => 6,
            'badges' => [],
            'perks' => [],
        ]);

        $result = app(GamificationEngine::class)->dispatch(
            GamificationEvent::GhostModeDailyLogin->value,
            ['user_id' => $user->id, 'metadata' => []],
        );

        $wallet = GamificationWallet::query()->where('user_id', $user->id)->first();

        $this->assertSame(7, $wallet->streak_days);
        $this->assertTrue($wallet->hasBadge('week_warrior'));
        $this->assertGreaterThan(0, $result['coins_delta']);
    }

    public function test_slip_discount_applies_reduced_penalty(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 50,
            'coins' => 20,
            'streak_days' => 3,
            'perks' => ['slip_discount_50'],
            'badges' => [],
        ]);

        $result = app(GamificationSlipHandler::class)->record('sent_message', [
            'user_id' => $user->id,
            'metadata' => ['trigger' => 'sent_message'],
        ]);

        $wallet = GamificationWallet::query()->where('user_id', $user->id)->first();

        $this->assertSame(43, $wallet->points);
        $this->assertSame(17, $wallet->coins);
        $this->assertNotContains('slip_discount_50', $wallet->perks ?? []);
        $this->assertLessThan(-10, $result['points_delta']);
        $this->assertGreaterThan(-15, $result['points_delta']);
    }
}
