<?php

namespace Tests\Feature\Billing;

use App\Services\Billing\BillingSchemaEnsurer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsureBillingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_is_idempotent_when_billing_schema_already_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'stripe_id'));
        $this->assertTrue(Schema::hasTable('subscriptions'));
        $this->assertTrue(Schema::hasTable('subscription_items'));
        $this->assertTrue(Schema::hasTable('stripe_plans'));

        $applied = app(BillingSchemaEnsurer::class)->ensure();

        $this->assertSame([], $applied);
    }
}
