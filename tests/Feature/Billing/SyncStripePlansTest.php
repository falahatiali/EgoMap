<?php

namespace Tests\Feature\Billing;

use App\Models\StripePlan;
use App\Services\Billing\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Collection;
use Stripe\Price;
use Tests\TestCase;

class SyncStripePlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_matching_local_plan(): void
    {
        $plan = StripePlan::factory()->create([
            'stripe_price_id' => 'price_existing',
            'name' => 'Old name',
            'unit_amount' => 999,
        ]);

        $price = Price::constructFrom([
            'id' => 'price_existing',
            'object' => 'price',
            'active' => true,
            'currency' => 'usd',
            'unit_amount' => 1500,
            'type' => 'recurring',
            'billing_scheme' => 'per_unit',
            'recurring' => ['interval' => 'month', 'interval_count' => 1],
            'product' => [
                'id' => 'prod_new',
                'object' => 'product',
                'name' => 'EgoMap Pro',
                'active' => true,
            ],
        ], null);

        $this->mock(StripeService::class)
            ->shouldReceive('all')
            ->once()
            ->andReturn(Collection::constructFrom([
                'object' => 'list',
                'data' => [$price],
                'has_more' => false,
            ], null));

        $this->artisan('billing:sync-stripe-plans')
            ->assertSuccessful()
            ->expectsOutputToContain('Synced 1 plan(s).');

        $plan->refresh();

        $this->assertSame('EgoMap Pro', $plan->name);
        $this->assertSame(1500, $plan->unit_amount);
        $this->assertSame(1, StripePlan::query()->count());
    }

    public function test_command_creates_plan_when_missing_locally(): void
    {
        $price = Price::constructFrom([
            'id' => 'price_new',
            'object' => 'price',
            'active' => true,
            'currency' => 'usd',
            'unit_amount' => 2000,
            'type' => 'recurring',
            'billing_scheme' => 'per_unit',
            'recurring' => ['interval' => 'month', 'interval_count' => 1],
            'product' => [
                'id' => 'prod_x',
                'object' => 'product',
                'name' => 'New plan',
                'active' => true,
            ],
        ], null);

        $this->mock(StripeService::class)
            ->shouldReceive('all')
            ->once()
            ->andReturn(Collection::constructFrom([
                'object' => 'list',
                'data' => [$price],
                'has_more' => false,
            ], null));

        $this->artisan('billing:sync-stripe-plans')
            ->assertSuccessful()
            ->expectsOutputToContain('Synced 1 plan(s).');

        $this->assertDatabaseHas('stripe_plans', [
            'stripe_price_id' => 'price_new',
            'stripe_product_id' => 'prod_x',
            'name' => 'New plan',
            'unit_amount' => 2000,
        ]);
    }
}
