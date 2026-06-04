<?php

namespace Tests\Feature\NoContact;

use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Models\User;
use App\Services\NoContact\PremiumUpsellService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\GamificationEngine\Models\GamificationWallet;
use Tests\TestCase;

class PremiumUpsellServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_first_offer_when_streak_meets_minimum(): void
    {
        $user = User::factory()->create();
        $protocol = $this->activeProtocol($user);
        $this->walletWithStreak($user, 3);

        $offer = app(PremiumUpsellService::class)->resolve($user, $protocol, 3);

        $this->assertNotNull($offer);
        $this->assertTrue($offer['show']);
        $this->assertSame('first', $offer['variant']);
        $this->assertSame(40, $offer['discount_percent']);
        $this->assertStringContainsString('UPSELL40', $offer['checkout_url']);
    }

    public function test_hides_for_pro_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('pro');
        $protocol = $this->activeProtocol($user);
        $this->walletWithStreak($user, 5);

        $offer = app(PremiumUpsellService::class)->resolve($user, $protocol, 5);

        $this->assertNull($offer);
    }

    public function test_defer_then_reminder_then_never(): void
    {
        Carbon::setTestNow('2026-06-01 12:00:00');

        $user = User::factory()->create();
        $protocol = $this->activeProtocol($user);
        $this->walletWithStreak($user, 4);

        $service = app(PremiumUpsellService::class);

        $service->defer($user, $protocol);
        $this->assertNull($service->resolve($user, $protocol, 4));

        Carbon::setTestNow('2026-06-05 12:00:00');
        $reminder = $service->resolve($user, $protocol, 4);

        $this->assertNotNull($reminder);
        $this->assertSame('reminder', $reminder['variant']);
        $this->assertSame(25, $reminder['discount_percent']);

        $service->defer($user, $protocol);
        $this->assertNull($service->resolve($user, $protocol, 4));

        Carbon::setTestNow('2026-06-10 12:00:00');
        $this->assertNull($service->resolve($user, $protocol, 4));
    }

    public function test_slip_reset_clears_upsell_state(): void
    {
        $user = User::factory()->create();
        $protocol = $this->activeProtocol($user);
        $wallet = $this->walletWithStreak($user, 3);

        $service = app(PremiumUpsellService::class);
        $service->defer($user, $protocol);

        $service->resetForNewStreak($user);

        $wallet->refresh();
        $user->refresh();

        $this->assertArrayNotHasKey('premium_upsell', $wallet->metadata ?? []);
        $this->assertSame(0, $user->premium_upsell_dismiss_count);
        $this->assertNull($user->premium_upsell_deferred_at);

        $offer = $service->resolve($user, $protocol, 3);
        $this->assertNotNull($offer);
        $this->assertSame('first', $offer['variant']);
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
