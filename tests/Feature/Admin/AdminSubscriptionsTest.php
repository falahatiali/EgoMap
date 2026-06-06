<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Livewire\Admin\Subscriptions\Index as SubscriptionsIndex;
use App\Models\StripePlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesSubscriptions;
use Tests\TestCase;

class AdminSubscriptionsTest extends TestCase
{
    use CreatesSubscriptions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_view_subscriptions_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::SuperAdmin->value);

        $subscriber = User::factory()->create([
            'name' => 'Paid Member',
            'email' => 'paid@example.com',
        ]);

        $plan = StripePlan::factory()->create([
            'interval' => 'month',
            'interval_count' => 1,
        ]);

        $this->createSubscription($subscriber, stripePriceId: $plan->stripe_price_id);

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertSee(__('admin.subscriptions.title'), false)
            ->assertSee('Paid Member', false)
            ->assertSee(__('pricing.period_monthly', [], 'en'), false)
            ->assertSee(__('admin.subscriptions.statuses.active'), false);
    }

    public function test_member_cannot_access_subscriptions_admin(): void
    {
        $member = User::factory()->create();
        $member->assignRole(RoleName::Member->value);

        Livewire::actingAs($member)
            ->test(SubscriptionsIndex::class)
            ->assertForbidden();
    }

    public function test_subscriptions_list_can_be_searched_by_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::SuperAdmin->value);

        $visible = User::factory()->create(['email' => 'visible@example.com']);
        $hidden = User::factory()->create(['email' => 'hidden@example.com']);

        $this->createSubscription($visible);
        $this->createSubscription($hidden);

        Livewire::actingAs($admin)
            ->test(SubscriptionsIndex::class)
            ->set('search', 'visible@example.com')
            ->assertSee('visible@example.com', false)
            ->assertDontSee('hidden@example.com', false);
    }
}
