<?php

namespace Tests\Feature\Billing;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\Billing\ProSubscriptionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class StripeSubscriptionTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_with_active_subscription_is_pro(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->createSubscription($user);

        $this->assertTrue($user->fresh()->isPro());
    }

    public function test_member_without_subscription_is_not_pro(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Member->value);

        $this->assertFalse($user->isPro());
    }

    public function test_pro_role_sync_assigns_and_removes_role_from_subscription_state(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_test_sync',
        ]);
        $user->assignRole(RoleName::Member->value);

        $service = app(ProSubscriptionService::class);

        $this->createSubscription($user);
        $service->syncProRole($user->fresh());

        $this->assertTrue($user->fresh()->hasRole(RoleName::Pro->value));

        $user->subscriptions()->update(['stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);
        $service->syncProRole($user->fresh());

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_cashier_webhook_route_is_registered(): void
    {
        $this->assertNotNull(route('cashier.webhook'));
        $this->assertStringContainsString('/stripe/webhook', route('cashier.webhook'));
    }

    public function test_cashier_payment_route_is_registered(): void
    {
        $this->assertNotNull(route('cashier.payment', ['id' => 'cs_test']));
    }
}
