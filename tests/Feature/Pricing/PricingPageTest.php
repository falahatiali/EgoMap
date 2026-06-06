<?php

namespace Tests\Feature\Pricing;

use App\Enums\RoleName;
use App\Livewire\Pricing\Show;
use App\Models\StripePlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pricing_page_renders_plans_in_monthly_quarterly_yearly_order(): void
    {
        StripePlan::factory()->create([
            'name' => 'Pro Yearly',
            'interval' => 'year',
            'interval_count' => 1,
            'unit_amount' => 49900,
            'active' => true,
            'is_recurring' => true,
        ]);

        StripePlan::factory()->create([
            'name' => 'Pro Quarterly',
            'interval' => 'month',
            'interval_count' => 3,
            'unit_amount' => 17999,
            'active' => true,
            'is_recurring' => true,
        ]);

        StripePlan::factory()->create([
            'name' => 'Pro Monthly',
            'interval' => 'month',
            'interval_count' => 1,
            'unit_amount' => 6999,
            'currency' => 'usd',
            'description' => 'Full Pro access for your recovery.',
            'active' => true,
            'is_recurring' => true,
        ]);

        $response = $this->get(route('pricing', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee(__('pricing.period_monthly', [], 'en'));
        $response->assertSee(__('pricing.period_quarterly', [], 'en'));
        $response->assertSee(__('pricing.period_yearly', [], 'en'));
        $response->assertSee('Full Pro access for your recovery.', false);
        $response->assertSee('$69.99', false);
        $response->assertSee('eg-pro-tiers', false);
    }

    public function test_pricing_page_shows_coupon_notice_from_query_string(): void
    {
        $response = $this->get(route('pricing', ['locale' => 'en', 'coupon' => 'UPSELL40']));

        $response->assertOk();
        $response->assertSee('UPSELL40');
    }

    public function test_guest_subscribe_redirects_to_login(): void
    {
        $plan = StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->get(route('pricing', ['locale' => 'en']))
            ->assertOk();

        Livewire::test(Show::class)
            ->call('subscribe', $plan->id)
            ->assertRedirect(route('login', ['locale' => 'en']));

        $this->assertSame($plan->id, session('pricing_intended_plan_id'));
    }

    public function test_subscribed_user_sees_current_plan_and_upgrade_options(): void
    {
        $user = User::factory()->create();

        $monthly = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'active' => true,
            'is_recurring' => true,
        ]);

        StripePlan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
            'active' => true,
            'is_recurring' => true,
        ]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_pricing',
            'stripe_status' => 'active',
            'stripe_price' => $monthly->stripe_price_id,
        ]);

        $user->subscriptions()->first()->items()->create([
            'stripe_id' => 'si_test_pricing',
            'stripe_product' => $monthly->stripe_product_id,
            'stripe_price' => $monthly->stripe_price_id,
        ]);

        $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertSee(__('pricing.active_plan', ['plan' => $monthly->billingPeriodName('en')], 'en'))
            ->assertSee(__('pricing.current_plan', [], 'en'))
            ->assertSee(__('pricing.upgrade_plan', [], 'en'));
    }

    public function test_super_admin_without_subscription_can_still_checkout(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::SuperAdmin->value);

        StripePlan::factory()->create([
            'active' => true,
            'is_recurring' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSee('wire:click="subscribe', false);
        $response->assertDontSee('eg-pricing-alert--success', false);
    }
}
