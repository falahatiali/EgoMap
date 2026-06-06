<?php

namespace Tests\Feature\Pricing;

use App\Listeners\RedirectToIntendedPricingPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PricingLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_sets_intended_url_to_resume_pricing_plan(): void
    {
        session(['pricing_intended_plan_id' => 42, 'locale' => 'en']);

        $user = User::factory()->create();

        app(RedirectToIntendedPricingPlan::class)->handle(new Login('web', $user, false));

        $this->assertSame(
            route('pricing', ['locale' => 'en']).'?resume_plan=42',
            session('url.intended'),
        );
    }

    public function test_login_redirects_to_pricing_resume_plan_after_authentication(): void
    {
        session(['pricing_intended_plan_id' => 7, 'locale' => 'en']);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        event(new Login('web', $user, false));

        $this->assertSame(
            route('pricing', ['locale' => 'en']).'?resume_plan=7',
            session('url.intended'),
        );
    }
}
