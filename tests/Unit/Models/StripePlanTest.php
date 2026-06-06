<?php

namespace Tests\Unit\Models;

use App\Models\StripePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_amount_major_converts_cents_to_dollars(): void
    {
        $plan = StripePlan::factory()->make([
            'currency' => 'usd',
            'unit_amount' => 6999,
        ]);

        $this->assertSame(69.99, $plan->unitAmountMajor());
    }

    public function test_formatted_price_uses_major_amount(): void
    {
        $plan = StripePlan::factory()->make([
            'currency' => 'usd',
            'unit_amount' => 6999,
        ]);

        $this->assertSame('$69.99', $plan->formattedPrice('en'));
    }

    public function test_billing_period_detects_monthly_quarterly_and_yearly(): void
    {
        $monthly = StripePlan::factory()->make(['interval' => 'month', 'interval_count' => 1]);
        $quarterly = StripePlan::factory()->make(['interval' => 'month', 'interval_count' => 3]);
        $yearly = StripePlan::factory()->make(['interval' => 'year', 'interval_count' => 1]);

        $this->assertSame('monthly', $monthly->billingPeriod());
        $this->assertSame('quarterly', $quarterly->billingPeriod());
        $this->assertSame('yearly', $yearly->billingPeriod());
        $this->assertSame('Monthly', $monthly->billingPeriodName('en'));
        $this->assertSame('Quarterly', $quarterly->billingPeriodName('en'));
        $this->assertSame('Yearly', $yearly->billingPeriodName('en'));
    }

    public function test_compare_tier_to_orders_monthly_before_yearly(): void
    {
        $monthly = StripePlan::factory()->make([
            'interval' => 'month',
            'interval_count' => 1,
        ]);

        $yearly = StripePlan::factory()->make([
            'interval' => 'year',
            'interval_count' => 1,
        ]);

        $this->assertLessThan(0, $monthly->compareTierTo($yearly));
        $this->assertGreaterThan(0, $yearly->compareTierTo($monthly));
    }

    public function test_ordered_for_display_sorts_monthly_quarterly_yearly(): void
    {
        $yearly = StripePlan::factory()->create(['interval' => 'year', 'interval_count' => 1, 'unit_amount' => 49900]);
        $quarterly = StripePlan::factory()->create(['interval' => 'month', 'interval_count' => 3, 'unit_amount' => 17999]);
        $monthly = StripePlan::factory()->create(['interval' => 'month', 'interval_count' => 1, 'unit_amount' => 6999]);

        $ordered = StripePlan::query()->orderedForDisplay()->pluck('id')->all();

        $this->assertSame([$monthly->id, $quarterly->id, $yearly->id], $ordered);
    }
}
