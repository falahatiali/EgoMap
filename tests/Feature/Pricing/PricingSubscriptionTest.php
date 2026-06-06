<?php

namespace Tests\Feature\Pricing;

use App\Enums\RoleName;
use App\Livewire\Pricing\Show;
use App\Models\StripePlan;
use App\Models\User;
use App\Services\Billing\PlanSelectionOutcome;
use App\Services\Billing\SubscriptionCheckoutService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class PricingSubscriptionTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_mount_reads_coupon_and_checkout_flash_states(): void
    {
        $this->get(route('pricing', ['locale' => 'en', 'coupon' => 'SAVE20', 'checkout' => 'success']))
            ->assertOk();

        Livewire::withQueryParams(['coupon' => 'SAVE20', 'checkout' => 'success'])
            ->test(Show::class)
            ->assertSet('coupon', 'SAVE20')
            ->assertSet('checkoutSuccess', true)
            ->assertSet('checkoutCancelled', false);
    }

    public function test_mount_reads_cancelled_checkout_state(): void
    {
        Livewire::withQueryParams(['checkout' => 'cancelled'])
            ->test(Show::class)
            ->assertSet('checkoutCancelled', true)
            ->assertSet('checkoutSuccess', false);
    }

    public function test_inactive_and_non_recurring_plans_are_hidden(): void
    {
        StripePlan::factory()->create([
            'active' => false,
            'is_recurring' => true,
            'interval' => 'month',
            'interval_count' => 1,
        ]);

        StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => false,
            'interval' => 'month',
            'interval_count' => 1,
        ]);

        $visible = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
            'interval' => 'month',
            'interval_count' => 1,
            'unit_amount' => 999,
            'currency' => 'usd',
        ]);

        $response = $this->get(route('pricing', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee('$9.99', false);
        $response->assertSee('wire:click="subscribe('.$visible->id.')"', false);
    }

    public function test_yearly_savings_badge_is_rendered(): void
    {
        StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'unit_amount' => 1000,
            'active' => true,
            'is_recurring' => true,
        ]);

        StripePlan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
            'unit_amount' => 10000,
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('pricing.save_percent', ['percent' => 17], 'en'), false);
    }

    public function test_pro_role_without_subscription_can_subscribe(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Pro->value);

        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->mock(SubscriptionCheckoutService::class)
            ->shouldReceive('selectPlan')
            ->once()
            ->with(
                Mockery::on(fn (User $u): bool => $u->is($user)),
                Mockery::on(fn (StripePlan $p): bool => $p->is($plan)),
                null,
            )
            ->andReturn(PlanSelectionOutcome::redirect('https://checkout.stripe.com/c/pay/cs_test_livewire'));

        Livewire::actingAs($user)
            ->test(Show::class)
            ->call('subscribe', $plan->id)
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_livewire');
    }

    public function test_subscribed_user_selecting_current_plan_dispatches_current_plan_event(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        $this->mock(SubscriptionCheckoutService::class)
            ->shouldReceive('selectPlan')
            ->once()
            ->andReturn(PlanSelectionOutcome::current());

        Livewire::actingAs($user)
            ->test(Show::class)
            ->call('subscribe', $plan->id)
            ->assertSet('subscribeError', __('pricing.error_current_plan'))
            ->assertDispatched('pricing-current-plan');
    }

    public function test_subscribed_user_can_upgrade_to_different_plan(): void
    {
        $user = User::factory()->create();

        $monthly = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'active' => true,
            'is_recurring' => true,
        ]);

        $yearly = StripePlan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $monthly->stripe_price_id);

        $this->mock(SubscriptionCheckoutService::class)
            ->shouldReceive('selectPlan')
            ->once()
            ->andReturn(PlanSelectionOutcome::changed());

        Livewire::actingAs($user)
            ->test(Show::class)
            ->call('subscribe', $yearly->id)
            ->assertSet('planChanged', true)
            ->assertDispatched('pricing-plan-changed');
    }

    public function test_authenticated_user_passes_coupon_to_checkout_service(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->mock(SubscriptionCheckoutService::class)
            ->shouldReceive('selectPlan')
            ->once()
            ->with(
                Mockery::type(User::class),
                Mockery::type(StripePlan::class),
                'UPSELL40',
            )
            ->andReturn(PlanSelectionOutcome::redirect('https://checkout.stripe.com/c/pay/cs_test_coupon'));

        Livewire::actingAs($user)
            ->withQueryParams(['coupon' => 'UPSELL40'])
            ->test(Show::class)
            ->call('subscribe', $plan->id)
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_coupon');
    }

    public function test_subscribe_rejects_inactive_plan(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'active' => false,
            'is_recurring' => true,
        ]);

        $this->mock(SubscriptionCheckoutService::class)->shouldNotReceive('selectPlan');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Show::class)
            ->call('subscribe', $plan->id);
    }
}
