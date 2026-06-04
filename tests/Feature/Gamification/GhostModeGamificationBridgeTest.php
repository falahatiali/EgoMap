<?php

namespace Tests\Feature\Gamification;

use App\Livewire\NoContact\Show;
use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationTransaction;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Tests\TestCase;

class GhostModeGamificationBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationEngineDatabaseSeeder::class);
    }

    public function test_analyze_blackhole_dispatches_analyzed_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        app(NoContactTimerService::class)->start(30);

        Livewire::test(Show::class)
            ->set('blackholeDraft', 'I miss you so much please come back')
            ->call('analyzeBlackhole')
            ->assertSet('blackholePhase', 'analyzed');

        $this->assertDatabaseHas('gamification_transactions', [
            'event' => GamificationEvent::BlackholeAnalyzed->value,
        ]);
    }

    public function test_confirm_destroy_increments_blackhole_tier_metadata(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'badges' => [],
            'perks' => [],
            'metadata' => ['blackhole_writes_total' => 4],
        ]);

        app(NoContactTimerService::class)->start(30);

        Livewire::test(Show::class)
            ->set('blackholeDraft', 'Do not send this angry text')
            ->call('analyzeBlackhole')
            ->call('confirmDestroyBlackhole');

        $wallet = GamificationWallet::query()->where('user_id', $user->id)->first();

        $this->assertSame(5, (int) ($wallet->metadata['blackhole_writes_total'] ?? 0));
        $this->assertSame(1, (int) ($wallet->metadata['blackhole_tier'] ?? 0));

        $this->assertDatabaseHas('gamification_transactions', [
            'event' => GamificationEvent::GhostModeBlackholeWrite->value,
        ]);
    }

    public function test_panic_challenge_dispatch_grants_coins(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'coins' => 0,
            'badges' => [],
            'perks' => [],
        ]);

        app(GamificationEngine::class)->dispatch(
            GamificationEvent::PanicChallengeCompleted->value,
            [
                'user_id' => $user->id,
                'metadata' => ['success' => true, 'breathing' => true, 'challenge' => false, 'emergency_count' => 1],
            ],
        );

        $wallet = GamificationWallet::query()->where('user_id', $user->id)->first();

        $this->assertGreaterThanOrEqual(5, $wallet->coins);

        $this->assertTrue(
            GamificationTransaction::query()
                ->where('event', GamificationEvent::PanicChallengeCompleted->value)
                ->exists(),
        );
    }
}
