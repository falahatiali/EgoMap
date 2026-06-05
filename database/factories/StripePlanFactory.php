<?php

namespace Database\Factories;

use App\Models\StripePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripePlan>
 */
class StripePlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stripe_price_id' => 'price_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'stripe_product_id' => 'prod_'.fake()->regexify('[A-Za-z0-9]{14}'),
            'name' => fake()->words(3, true),
            'nickname' => null,
            'description' => fake()->optional()->sentence(),
            'currency' => 'usd',
            'unit_amount' => fake()->numberBetween(999, 4999),
            'interval' => 'month',
            'interval_count' => 1,
            'billing_scheme' => 'per_unit',
            'active' => true,
            'is_recurring' => true,
            'lookup_key' => null,
            'subscription_type' => 'default',
            'metadata' => null,
            'synced_at' => null,
        ];
    }
}
