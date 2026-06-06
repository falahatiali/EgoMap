<?php

namespace Tests\Unit\Billing;

use App\Models\StripePlan;
use App\Models\User;
use App\Services\Billing\SubscriptionPlanResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class SubscriptionPlanResolverTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    public function test_resolves_current_plan_from_subscription_price(): void
    {
        $user = User::factory()->create();

        $plan = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        $resolved = app(SubscriptionPlanResolver::class)->currentPlanFor($user->fresh());

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($plan));
    }

    public function test_plan_relation_marks_current_upgrade_and_downgrade(): void
    {
        $monthly = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
        ]);

        $yearly = StripePlan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
        ]);

        $resolver = app(SubscriptionPlanResolver::class);

        $this->assertSame('current', $resolver->planRelation($monthly, $monthly));
        $this->assertSame('upgrade', $resolver->planRelation($yearly, $monthly));
        $this->assertSame('downgrade', $resolver->planRelation($monthly, $yearly));
        $this->assertNull($resolver->planRelation($monthly, null));
    }
}
