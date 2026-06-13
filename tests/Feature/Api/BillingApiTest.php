<?php

namespace Tests\Feature\Api;

use App\Models\StripePlan;
use App\Models\User;
use App\Services\Billing\CheckoutReturnUrls;
use App\Services\Billing\PlanSelectionOutcome;
use App\Services\Billing\SubscriptionCheckoutService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_billing_catalog_requires_authentication(): void
    {
        $this->getJson('/api/v1/billing')
            ->assertUnauthorized();
    }

    public function test_billing_catalog_returns_plans_and_subscription_state(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $monthly = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'unit_amount' => 999,
            'active' => true,
            'is_recurring' => true,
        ]);

        StripePlan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
            'unit_amount' => 9999,
            'active' => true,
            'is_recurring' => true,
        ]);

        StripePlan::factory()->create([
            'active' => false,
            'is_recurring' => true,
        ]);

        $response = $this->getJson('/api/v1/billing');

        $response->assertOk()
            ->assertJsonPath('subscription.active', false)
            ->assertJsonPath('subscription.is_pro', false)
            ->assertJsonPath('subscription.current_plan', null)
            ->assertJsonCount(2, 'plans')
            ->assertJsonPath('plans.0.id', $monthly->id)
            ->assertJsonPath('plans.0.billing_period', 'monthly')
            ->assertJsonStructure([
                'subscription' => ['active', 'is_pro', 'has_incomplete_payment', 'current_plan'],
                'plans' => [
                    ['id', 'billing_period', 'name', 'price', 'relation', 'cta_label', 'selectable'],
                ],
                'features' => ['free', 'pro'],
                'labels' => ['page_title', 'hero_title', 'secure_checkout'],
            ]);
    }

    public function test_billing_catalog_marks_current_plan_for_subscriber(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        Sanctum::actingAs($user->fresh());

        $this->getJson('/api/v1/billing')
            ->assertOk()
            ->assertJsonPath('subscription.active', true)
            ->assertJsonPath('subscription.is_pro', true)
            ->assertJsonPath('subscription.current_plan.id', $plan->id)
            ->assertJsonPath('plans.0.relation', 'current')
            ->assertJsonPath('plans.0.selectable', false);
    }

    public function test_checkout_requires_authentication(): void
    {
        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->postJson('/api/v1/billing/checkout', ['plan_id' => $plan->id])
            ->assertUnauthorized();
    }

    public function test_checkout_returns_redirect_url_from_service(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->mock(SubscriptionCheckoutService::class)
            ->shouldReceive('selectPlan')
            ->once()
            ->with(
                Mockery::on(fn (User $candidate): bool => $candidate->is($user)),
                Mockery::on(fn (StripePlan $candidate): bool => $candidate->is($plan)),
                null,
                Mockery::type(CheckoutReturnUrls::class),
            )
            ->andReturn(PlanSelectionOutcome::redirect('https://checkout.stripe.com/test-session'));

        $this->postJson('/api/v1/billing/checkout', ['plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('outcome', PlanSelectionOutcome::Redirect)
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.com/test-session')
            ->assertJsonMissing(['labels']);
    }

    public function test_checkout_returns_changed_outcome_for_plan_swap(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->mock(SubscriptionCheckoutService::class)
            ->shouldReceive('selectPlan')
            ->once()
            ->andReturn(PlanSelectionOutcome::changed());

        $this->postJson('/api/v1/billing/checkout', ['plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('outcome', PlanSelectionOutcome::Changed);
    }

    public function test_checkout_rejects_inactive_plan(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $plan = StripePlan::factory()->create([
            'active' => false,
            'is_recurring' => true,
        ]);

        $this->postJson('/api/v1/billing/checkout', ['plan_id' => $plan->id])
            ->assertNotFound();
    }

    public function test_confirm_checkout_requires_session_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/billing/checkout/confirm', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['session_id']);
    }
}
