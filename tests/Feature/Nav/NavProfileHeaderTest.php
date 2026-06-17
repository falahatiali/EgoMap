<?php

namespace Tests\Feature\Nav;

use App\Models\StripePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class NavProfileHeaderTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    public function test_header_shows_user_name_instead_of_profile_label(): void
    {
        $user = User::factory()->create(['name' => 'Jordan Lee']);

        $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('eg-nav-user-menu', false)
            ->assertSee('Jordan Lee', false);
    }

    public function test_header_shows_plan_badge_for_subscribed_user(): void
    {
        $user = User::factory()->create(['name' => 'Jordan Lee']);

        $plan = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 3,
            'active' => true,
            'is_recurring' => true,
        ]);

        $this->createSubscription($user, stripePriceId: $plan->stripe_price_id);

        $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('eg-nav-plan-badge', false)
            ->assertSee(__('pricing.period_quarterly', [], 'en'), false);
    }

    public function test_header_does_not_show_language_switcher(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pricing', ['locale' => 'en']))
            ->assertOk()
            ->assertDontSee('eg-lang-switch', false);
    }
}
