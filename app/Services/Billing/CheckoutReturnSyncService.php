<?php

namespace App\Services\Billing;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Stripe\Checkout\Session;
use Stripe\Subscription as StripeSubscription;

class CheckoutReturnSyncService
{
    public function __construct(
        private readonly ProSubscriptionService $proSubscriptions,
    ) {}

    public function syncFromCheckoutSession(User $user, string $sessionId): void
    {
        if ($sessionId === '' || $user->stripe_id === null) {
            return;
        }

        $session = $user->stripe()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'subscription.items.data.price'],
        ]);

        if ($session->customer !== $user->stripe_id) {
            return;
        }

        if ($session->status !== Session::STATUS_COMPLETE) {
            return;
        }

        $stripeSubscription = $session->subscription;

        if ($stripeSubscription === null) {
            return;
        }

        if (is_string($stripeSubscription)) {
            $stripeSubscription = $user->stripe()->subscriptions->retrieve($stripeSubscription, [
                'expand' => ['items.data.price'],
            ]);
        }

        $this->syncSubscription($user, $stripeSubscription);
        $this->proSubscriptions->syncProRole($user->fresh());
    }

    private function syncSubscription(User $user, StripeSubscription $stripeSubscription): void
    {
        $firstItem = $stripeSubscription->items->data[0] ?? null;

        if ($firstItem === null) {
            return;
        }

        $isSinglePrice = count($stripeSubscription->items->data) === 1;
        $subscriptionName = (string) config('billing.subscription_name', 'default');

        /** @var Subscription $subscription */
        $subscription = $user->subscriptions()->updateOrCreate([
            'stripe_id' => $stripeSubscription->id,
        ], [
            'type' => $stripeSubscription->metadata['type']
                ?? $stripeSubscription->metadata['name']
                ?? $subscriptionName,
            'stripe_status' => $stripeSubscription->status,
            'stripe_price' => $isSinglePrice ? $firstItem->price->id : null,
            'quantity' => $isSinglePrice ? ($firstItem->quantity ?? null) : null,
            'trial_ends_at' => isset($stripeSubscription->trial_end)
                ? Carbon::createFromTimestamp($stripeSubscription->trial_end)
                : null,
            'ends_at' => null,
        ]);

        $subscriptionItemIds = [];

        foreach ($stripeSubscription->items->data as $item) {
            $subscriptionItemIds[] = $item->id;

            $subscription->items()->updateOrCreate([
                'stripe_id' => $item->id,
            ], [
                'stripe_product' => is_string($item->price->product)
                    ? $item->price->product
                    : $item->price->product->id,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity ?? null,
            ]);
        }

        $subscription->items()->whereNotIn('stripe_id', $subscriptionItemIds)->delete();
    }
}
