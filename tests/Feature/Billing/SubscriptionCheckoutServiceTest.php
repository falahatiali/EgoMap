<?php

namespace Tests\Feature\Billing;

use App\Models\StripePlan;
use App\Models\User;
use App\Services\Billing\PlanSelectionOutcome;
use App\Services\Billing\SubscriptionCheckoutService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Checkout;
use RuntimeException;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class SubscriptionCheckoutServiceTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_select_plan_returns_current_when_user_is_already_on_plan(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'subscription_type' => 'default',
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        $outcome = app(SubscriptionCheckoutService::class)->selectPlan($user->fresh(), $plan);

        $this->assertSame(PlanSelectionOutcome::Current, $outcome->type);
    }

    public function test_checkout_allows_when_only_other_subscription_type_is_active(): void
    {
        $user = User::factory()->create();
        $this->createSubscription($user, type: 'teams');

        $plan = StripePlan::factory()->create([
            'subscription_type' => 'default',
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'type' => 'teams',
            'stripe_status' => 'active',
        ]);

        $this->assertFalse($user->fresh()->subscribed('default'));

        try {
            $result = app(SubscriptionCheckoutService::class)->checkout($user->fresh(), $plan);

            $this->assertInstanceOf(Checkout::class, $result);
        } catch (\Throwable $throwable) {
            $this->assertStringNotContainsString(
                'User already has an active subscription.',
                $throwable->getMessage(),
                'Checkout should not block users subscribed to a different subscription type.',
            );
        }
    }

    public function test_swap_plan_throws_when_user_is_already_on_plan(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'subscription_type' => 'default',
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User is already on this plan.');

        app(SubscriptionCheckoutService::class)->swapPlan($user->fresh(), $plan);
    }

    public function test_super_admin_without_subscription_is_not_considered_subscribed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->assertFalse($user->hasActiveSubscription());
        $this->assertTrue($user->isPro());
    }
}
