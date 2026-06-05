<?php

namespace App\Services\Billing;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProSubscriptionService
{
    public function syncProRole(User $user): void
    {
        if (! config('billing.sync_pro_role', true)) {
            return;
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return;
        }

        $subscriptionName = (string) config('billing.subscription_name', 'default');
        $shouldHavePro = $user->subscribed($subscriptionName);

        if ($shouldHavePro && ! $user->hasRole(RoleName::Pro->value)) {
            $user->assignRole(RoleName::Pro->value);

            Log::info('Assigned pro role from Stripe subscription.', [
                'user_id' => $user->id,
            ]);

            return;
        }

        if (! $shouldHavePro && $user->hasRole(RoleName::Pro->value)) {
            $user->removeRole(RoleName::Pro->value);

            Log::info('Removed pro role after Stripe subscription ended.', [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function syncFromWebhookPayload(array $payload): void
    {
        $type = (string) ($payload['type'] ?? '');

        if (! in_array($type, [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
        ], true)) {
            return;
        }

        $customerId = data_get($payload, 'data.object.customer');

        if (! is_string($customerId) || $customerId === '') {
            return;
        }

        $user = User::query()->where('stripe_id', $customerId)->first();

        if ($user === null) {
            return;
        }

        $this->syncProRole($user);
    }
}
