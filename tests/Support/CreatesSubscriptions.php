<?php

namespace Tests\Support;

use App\Models\User;
use Laravel\Cashier\Subscription;

trait CreatesSubscriptions
{
    protected function createSubscription(
        User $user,
        string $status = 'active',
        ?string $type = 'default',
        ?\DateTimeInterface $endsAt = null,
        ?\DateTimeInterface $trialEndsAt = null,
        ?string $stripePriceId = null,
    ): Subscription {
        $subscription = $user->subscriptions()->create([
            'type' => $type ?? 'default',
            'stripe_id' => 'sub_test_'.fake()->unique()->uuid(),
            'stripe_status' => $status,
            'stripe_price' => $stripePriceId,
            'ends_at' => $endsAt,
            'trial_ends_at' => $trialEndsAt,
        ]);

        if (is_string($stripePriceId) && $stripePriceId !== '') {
            $subscription->items()->create([
                'stripe_id' => 'si_test_'.fake()->unique()->uuid(),
                'stripe_product' => 'prod_test_'.fake()->regexify('[A-Za-z0-9]{8}'),
                'stripe_price' => $stripePriceId,
            ]);
        }

        return $subscription;
    }
}
