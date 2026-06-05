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
}
