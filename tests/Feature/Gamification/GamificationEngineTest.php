<?php

namespace Tests\Feature\Gamification;

use App\Livewire\NoContact\Show;
use App\Models\User;
use App\Services\NoContact\NoContactTimerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationCatalogService;
use Modules\GamificationEngine\Services\GamificationEngine;
use Tests\TestCase;

class GamificationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationEngineDatabaseSeeder::class);
    }

    public function test_dispatch_applies_matching_reward_rules(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $result = app(GamificationEngine::class)->dispatch(
            GamificationEvent::GhostModeActivated->value,
            ['user_id' => $user->id, 'metadata' => []],
        );

        $wallet = GamificationWallet::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($wallet);
        $this->assertSame(20, $wallet->points);
        $this->assertSame(10, $wallet->coins);
        $this->assertTrue($wallet->hasBadge('first_day'));
        $this->assertSame(20, $result['points_delta']);
    }

    public function test_slip_penalty_matches_trigger_condition(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $engine = app(GamificationEngine::class);
        $wallet = GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 50,
            'coins' => 20,
            'streak_days' => 5,
            'badges' => [],
            'perks' => [],
        ]);

        $engine->dispatch(
            GamificationEvent::GhostModeSlipReported->value,
            ['user_id' => $user->id, 'metadata' => ['trigger' => 'sent_message']],
        );

        $wallet->refresh();

        $this->assertSame(36, $wallet->points);
        $this->assertSame(0, $wallet->streak_days);
    }

    public function test_admin_can_list_rules(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification.rules.index'))
            ->assertOk()
            ->assertSee('ghost_activate', false);
    }

    public function test_ghost_mode_dispatches_slip_event(): void
    {
        Carbon::setTestNow('2026-05-25 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        app(NoContactTimerService::class)->start(30);

        Livewire::test(Show::class)
            ->call('beginSlipTriage')
            ->set('slipTrigger', 'felt_weak')
            ->call('recordSlip')
            ->assertSet('rewardToasts', fn (array $toasts): bool => count($toasts) > 0);

        $this->assertDatabaseHas('gamification_transactions', [
            'event' => GamificationEvent::GhostModeSlipReported->value,
        ]);
    }

    public function test_preview_returns_rules_without_applying(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $preview = app(GamificationEngine::class)->preview(
            GamificationEvent::GhostModeSlipReported->value,
            ['metadata' => ['trigger' => 'checked_profile']],
        );

        $this->assertNotEmpty($preview);
        $this->assertSame(0, GamificationWallet::query()->count());
    }

    public function test_purchase_shop_item_deducts_coins(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'coins' => 200,
            'badges' => [],
            'perks' => [],
        ]);

        $result = app(GamificationEngine::class)->purchaseShopItem('streak_freeze', ['user_id' => $user->id]);

        $this->assertTrue($result['success']);
        $this->assertSame(100, $result['wallet']['coins']);
        $this->assertSame(1, $result['wallet']['metadata']['streak_freeze_charges']);
    }

    public function test_consume_perk_removes_from_wallet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        GamificationWallet::query()->create([
            'user_id' => $user->id,
            'perks' => ['free_shield_repair'],
            'badges' => [],
        ]);

        $result = app(GamificationEngine::class)->consumePerk('free_shield_repair', ['user_id' => $user->id]);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['wallet']['perks']);
    }

    public function test_admin_badges_page_loads(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification.badges.index'))
            ->assertOk();
    }

    public function test_admin_catalog_lists_rewards_and_penalties(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification.catalog'))
            ->assertOk()
            ->assertSee('ghost_slip_message', false)
            ->assertSee('ghost_activate', false);
    }

    public function test_admin_transactions_page_loads(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        app(GamificationEngine::class)->dispatch(
            GamificationEvent::GhostModeActivated->value,
            ['user_id' => $user->id, 'metadata' => []],
        );

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification.transactions.index'))
            ->assertOk()
            ->assertSee(GamificationEvent::GhostModeActivated->value, false);
    }

    public function test_catalog_service_overview_counts_rewards(): void
    {
        $overview = app(GamificationCatalogService::class)->overview();

        $this->assertGreaterThan(0, $overview['rewards_count']);
        $this->assertGreaterThan(0, $overview['penalties_count']);
    }
}
