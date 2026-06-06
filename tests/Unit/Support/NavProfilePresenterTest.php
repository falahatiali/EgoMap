<?php

namespace Tests\Unit\Support;

use App\Models\StripePlan;
use App\Models\User;
use App\Support\NavProfilePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class NavProfilePresenterTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    public function test_presenter_returns_name_without_badge_for_free_user(): void
    {
        $user = User::factory()->create(['name' => 'Sara Ahmadi']);

        $profile = app(NavProfilePresenter::class)->forUser($user);

        $this->assertNotNull($profile);
        $this->assertSame('Sara Ahmadi', $profile->name);
        $this->assertSame('S', $profile->initial);
        $this->assertNull($profile->planBadge);
    }

    public function test_presenter_returns_plan_badge_for_subscribed_user(): void
    {
        $user = User::factory()->create(['name' => 'Alex']);

        $plan = StripePlan::factory()->create([
            'interval' => 'year',
            'interval_count' => 1,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        $profile = app(NavProfilePresenter::class)->forUser($user->fresh());

        $this->assertSame('Yearly', $profile->planBadge);
        $this->assertSame('yearly', $profile->planPeriod);
    }
}
