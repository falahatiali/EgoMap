<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Modules\GamificationEngine\Database\Seeders\GamificationEngineDatabaseSeeder;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Models\GamificationWallet;
use Modules\GamificationEngine\Services\GamificationEngine;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRewardsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(GamificationEngineDatabaseSeeder::class);
    }

    public function test_rewards_page_requires_auth(): void
    {
        $this->get(route('profile.rewards', ['locale' => 'en']))
            ->assertRedirect(route('login', ['locale' => 'en']));
    }

    public function test_rewards_page_shows_wallet_and_ledger(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        app(GamificationEngine::class)->dispatch(
            GamificationEvent::GhostModeActivated->value,
            ['user_id' => $user->id, 'metadata' => []],
        );

        $this->get(route('profile.rewards', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('profile.rewards_title'), false)
            ->assertSee(__('profile.rewards_ledger_title'), false)
            ->assertSee(__('gamification.events.ghost_mode_activated'), false);

        $this->assertNotNull(GamificationWallet::query()->where('user_id', $user->id)->first());
    }

    public function test_profile_page_links_to_rewards_dossier(): void
    {
        $user = User::factory()->recoveryWithAdvancedUnlocked()->create();

        $this->actingAs($user)
            ->get(route('profile', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(route('profile.rewards', ['locale' => 'en']), false)
            ->assertSee(__('profile.rewards_card_title'), false);
    }
}
