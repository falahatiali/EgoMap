<?php

namespace Tests\Feature\Billing;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_without_subscription_is_pro_but_not_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::SuperAdmin->value);

        $this->assertTrue($user->isPro());
        $this->assertFalse($user->hasActiveSubscription());
    }

    public function test_admin_without_subscription_is_not_pro_and_not_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Admin->value);

        $this->assertFalse($user->isPro());
        $this->assertFalse($user->hasActiveSubscription());
    }

    public function test_pro_role_without_subscription_is_pro_but_not_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Pro->value);

        $this->assertTrue($user->isPro());
        $this->assertFalse($user->hasActiveSubscription());
    }

    public function test_member_with_active_subscription_is_pro_and_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user);

        $user = $user->fresh();

        $this->assertTrue($user->hasActiveSubscription());
        $this->assertTrue($user->isPro());
    }

    public function test_member_without_subscription_is_neither_pro_nor_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->assertFalse($user->hasActiveSubscription());
        $this->assertFalse($user->isPro());
    }

    public function test_canceled_subscription_in_grace_period_is_still_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user, status: 'canceled', endsAt: now()->addWeek());

        $this->assertTrue($user->fresh()->hasActiveSubscription());
        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_canceled_subscription_after_grace_period_is_not_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user, status: 'canceled', endsAt: now()->subDay());

        $this->assertFalse($user->fresh()->hasActiveSubscription());
        $this->assertFalse($user->fresh()->isPro());
    }

    public function test_trialing_subscription_counts_as_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user, status: 'trialing', trialEndsAt: now()->addDays(7));

        $this->assertTrue($user->fresh()->hasActiveSubscription());
    }

    public function test_past_due_subscription_is_not_subscribed_by_default(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user, status: 'past_due');

        $this->assertFalse($user->fresh()->hasActiveSubscription());
    }

    public function test_incomplete_subscription_is_not_subscribed_by_default(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user, status: 'incomplete');

        $this->assertFalse($user->fresh()->hasActiveSubscription());
    }

    public function test_user_with_premium_permission_is_pro_without_subscription(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);
        $user->givePermissionTo(Permission::ReportsViewPremium->value);

        $this->assertFalse($user->hasActiveSubscription());
        $this->assertTrue($user->isPro());
    }

    #[DataProvider('subscriptionTypeProvider')]
    public function test_has_active_subscription_respects_subscription_type(string $type, bool $matchesDefault): void
    {
        $user = User::factory()->create();

        $this->createSubscription($user, type: $type);

        $this->assertSame($matchesDefault, $user->fresh()->hasActiveSubscription());
        $this->assertSame($matchesDefault, $user->fresh()->hasActiveSubscription('default'));
        $this->assertSame($type === 'teams', $user->fresh()->hasActiveSubscription('teams'));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function subscriptionTypeProvider(): array
    {
        return [
            'default type' => ['default', true],
            'other type' => ['teams', false],
        ];
    }
}
