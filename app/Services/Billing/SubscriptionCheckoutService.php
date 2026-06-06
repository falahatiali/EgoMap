<?php

namespace App\Services\Billing;

use App\Models\StripePlan;
use App\Models\User;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Exceptions\InvalidCoupon;
use Laravel\Cashier\Exceptions\SubscriptionUpdateFailure;
use Laravel\Cashier\Subscription;
use Throwable;

class SubscriptionCheckoutService
{
    public function selectPlan(User $user, StripePlan $plan, ?string $coupon = null): PlanSelectionOutcome
    {
        $subscriptionType = $plan->resolvedSubscriptionType();

        try {
            if ($user->hasIncompletePayment($subscriptionType)) {
                $subscription = $user->subscription($subscriptionType);

                if ($subscription !== null) {
                    $payment = $subscription->latestPayment();

                    if ($payment !== null) {
                        return PlanSelectionOutcome::redirect(route('cashier.payment', [
                            $payment->id,
                            'redirect' => route('pricing', LocaleConfig::routeParameters()),
                        ]));
                    }
                }
            }

            if ($user->subscribed($subscriptionType)) {
                return $this->changeExistingPlan($user, $plan);
            }

            return $this->startCheckout($user, $plan, $coupon);
        } catch (Throwable $throwable) {
            Log::warning('Subscription plan selection failed.', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'message' => $throwable->getMessage(),
            ]);

            return PlanSelectionOutcome::error($this->userFacingMessage($throwable));
        }
    }

    /**
     * @deprecated Use selectPlan() instead.
     */
    public function checkout(User $user, StripePlan $plan, ?string $coupon = null): Checkout
    {
        return $this->buildCheckout($user, $plan, $coupon);
    }

    /**
     * @deprecated Use selectPlan() instead.
     */
    public function swapPlan(User $user, StripePlan $plan): Subscription
    {
        $subscription = $user->subscription($plan->resolvedSubscriptionType());

        if ($subscription === null || ! $subscription->valid()) {
            throw new \RuntimeException('User does not have an active subscription to change.');
        }

        $currentPriceId = $subscription->stripe_price
            ?? $subscription->items->first()?->stripe_price;

        if ($currentPriceId === $plan->stripe_price_id) {
            throw new \RuntimeException('User is already on this plan.');
        }

        return $subscription->swapAndInvoice($plan->stripe_price_id);
    }

    private function changeExistingPlan(User $user, StripePlan $plan): PlanSelectionOutcome
    {
        $subscription = $user->subscription($plan->resolvedSubscriptionType());

        if ($subscription === null || ! $subscription->valid()) {
            return PlanSelectionOutcome::error(__('pricing.error_subscription_state'));
        }

        $currentPriceId = $subscription->stripe_price
            ?? $subscription->items->first()?->stripe_price;

        if ($currentPriceId === $plan->stripe_price_id) {
            return PlanSelectionOutcome::current();
        }

        try {
            $subscription->swapAndInvoice($plan->stripe_price_id);
        } catch (IncompletePayment $incompletePayment) {
            return PlanSelectionOutcome::redirect(route('cashier.payment', [
                $incompletePayment->payment->id,
                'redirect' => route('pricing', LocaleConfig::routeParameters()),
            ]));
        } catch (SubscriptionUpdateFailure $failure) {
            return PlanSelectionOutcome::error(__('pricing.error_plan_change_failed'));
        }

        return PlanSelectionOutcome::changed();
    }

    private function startCheckout(User $user, StripePlan $plan, ?string $coupon): PlanSelectionOutcome
    {
        $session = $this->buildCheckout($user, $plan, $coupon);
        $url = $session->url;

        if (! is_string($url) || $url === '') {
            return PlanSelectionOutcome::error(__('pricing.error_checkout_failed'));
        }

        return PlanSelectionOutcome::redirect($url);
    }

    private function buildCheckout(User $user, StripePlan $plan, ?string $coupon = null): Checkout
    {
        $builder = $user->newSubscription($plan->resolvedSubscriptionType(), $plan->stripe_price_id);

        if (is_string($coupon) && $coupon !== '') {
            try {
                $builder->withCoupon($coupon);
            } catch (InvalidCoupon) {
                $builder->allowPromotionCodes();
            }
        } else {
            $builder->allowPromotionCodes();
        }

        $pricingUrl = route('pricing', LocaleConfig::routeParameters());

        return $builder->checkout([
            'success_url' => $pricingUrl.'?checkout=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $pricingUrl.'?checkout=cancelled',
        ]);
    }

    private function userFacingMessage(Throwable $throwable): string
    {
        if ($throwable instanceof \RuntimeException) {
            return match ($throwable->getMessage()) {
                'User already has an active subscription.' => __('pricing.error_already_subscribed'),
                'User is already on this plan.' => __('pricing.error_current_plan'),
                'User does not have an active subscription to change.' => __('pricing.error_subscription_state'),
                default => __('pricing.error_checkout_failed'),
            };
        }

        return __('pricing.error_checkout_failed');
    }
}
