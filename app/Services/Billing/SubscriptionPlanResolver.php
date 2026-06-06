<?php

namespace App\Services\Billing;

use App\Models\StripePlan;
use App\Models\User;

class SubscriptionPlanResolver
{
    public function currentPlanFor(User $user): ?StripePlan
    {
        $subscriptionName = (string) config('billing.subscription_name', 'default');

        if (! $user->subscribed($subscriptionName)) {
            return null;
        }

        $subscription = $user->subscription($subscriptionName);

        if ($subscription === null) {
            return null;
        }

        $priceId = $subscription->stripe_price
            ?? $subscription->items->first()?->stripe_price;

        if (! is_string($priceId) || $priceId === '') {
            return null;
        }

        return StripePlan::query()
            ->where('stripe_price_id', $priceId)
            ->first();
    }

    public function planRelation(StripePlan $candidate, ?StripePlan $current): ?string
    {
        if ($current === null) {
            return null;
        }

        if ($candidate->stripe_price_id === $current->stripe_price_id) {
            return 'current';
        }

        return $candidate->compareTierTo($current) > 0 ? 'upgrade' : 'downgrade';
    }
}
