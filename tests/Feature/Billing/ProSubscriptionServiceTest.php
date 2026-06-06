<?php

namespace Tests\Feature\Billing;

use App\Enums\RoleName;
use App\Listeners\SyncProRoleFromStripeWebhook;
use App\Models\User;
use App\Services\Billing\ProSubscriptionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class ProSubscriptionServiceTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sync_assigns_pro_role_when_subscription_is_active(): void
    {
        $user = $this->memberWithStripeCustomer();
        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncProRole($user->fresh());

        $this->assertTrue($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_sync_removes_pro_role_when_subscription_ended(): void
    {
        $user = $this->memberWithStripeCustomer();
        $user->assignRole(RoleName::Pro->value);

        $this->createSubscription($user, status: 'canceled', endsAt: now()->subDay());

        app(ProSubscriptionService::class)->syncProRole($user->fresh());

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_sync_does_not_duplicate_pro_role(): void
    {
        $user = $this->memberWithStripeCustomer();
        $user->assignRole(RoleName::Pro->value);

        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncProRole($user->fresh());

        $this->assertSame(1, $user->fresh()->roles()->where('name', RoleName::Pro->value)->count());
    }

    public function test_sync_skips_when_disabled_in_config(): void
    {
        config(['billing.sync_pro_role' => false]);

        $user = $this->memberWithStripeCustomer();
        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncProRole($user->fresh());

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_sync_skips_super_admin(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_admin']);
        $user->assignRole(RoleName::SuperAdmin->value);

        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncProRole($user->fresh());

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_sync_skips_admin(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_admin']);
        $user->assignRole(RoleName::Admin->value);

        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncProRole($user->fresh());

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_webhook_payload_ignores_unrelated_events(): void
    {
        $user = $this->memberWithStripeCustomer();

        app(ProSubscriptionService::class)->syncFromWebhookPayload([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['customer' => 'cus_test_member']],
        ]);

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_webhook_payload_ignores_missing_customer(): void
    {
        $user = $this->memberWithStripeCustomer();
        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncFromWebhookPayload([
            'type' => 'customer.subscription.updated',
            'data' => ['object' => []],
        ]);

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_webhook_payload_ignores_unknown_customer(): void
    {
        $user = $this->memberWithStripeCustomer();

        app(ProSubscriptionService::class)->syncFromWebhookPayload([
            'type' => 'customer.subscription.created',
            'data' => ['object' => ['customer' => 'cus_unknown']],
        ]);

        $this->assertFalse($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_webhook_payload_syncs_pro_role_for_subscription_created(): void
    {
        $user = $this->memberWithStripeCustomer();
        $this->createSubscription($user);

        app(ProSubscriptionService::class)->syncFromWebhookPayload([
            'type' => 'customer.subscription.created',
            'data' => ['object' => ['customer' => 'cus_test_member']],
        ]);

        $this->assertTrue($user->fresh()->hasRole(RoleName::Pro->value));
    }

    public function test_webhook_listener_delegates_to_service(): void
    {
        Event::fake();

        Event::assertListening(WebhookHandled::class, SyncProRoleFromStripeWebhook::class);
    }

    private function memberWithStripeCustomer(): User
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test_member']);
        $user->assignRole(RoleName::Member->value);

        return $user;
    }
}
