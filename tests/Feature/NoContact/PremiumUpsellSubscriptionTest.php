<?php

namespace Tests\Feature\NoContact;

use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Models\User;
use App\Services\NoContact\PremiumUpsellService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GamificationEngine\Models\GamificationWallet;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class PremiumUpsellSubscriptionTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_hides_upsell_for_member_with_active_subscription(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');
        $this->createSubscription($user);

        $protocol = $this->activeProtocol($user);
        $this->walletWithStreak($user, 5);

        $offer = app(PremiumUpsellService::class)->resolve($user, $protocol, 5);

        $this->assertNull($offer);
        $this->assertFalse($user->hasRole('pro'));
        $this->assertTrue($user->hasActiveSubscription());
    }

    public function test_checkout_url_points_to_pricing_with_coupon(): void
    {
        $user = User::factory()->create();
        $protocol = $this->activeProtocol($user);
        $this->walletWithStreak($user, 3);

        $offer = app(PremiumUpsellService::class)->resolve($user, $protocol, 3);

        $this->assertNotNull($offer);
        $this->assertStringContainsString('/en/pricing', $offer['checkout_url']);
        $this->assertStringContainsString('coupon=UPSELL40', $offer['checkout_url']);
    }

    private function activeProtocol(User $user): NoContactProtocol
    {
        return NoContactProtocol::factory()->create([
            'user_id' => $user->id,
            'status' => NoContactStatus::Active,
            'streak_started_at' => now()->subDays(4),
            'target_ends_at' => now()->addDays(86),
        ]);
    }

    private function walletWithStreak(User $user, int $streakDays): GamificationWallet
    {
        return GamificationWallet::query()->create([
            'user_id' => $user->id,
            'points' => 0,
            'coins' => 0,
            'streak_days' => $streakDays,
            'badges' => [],
            'perks' => [],
            'metadata' => [],
        ]);
    }
}
