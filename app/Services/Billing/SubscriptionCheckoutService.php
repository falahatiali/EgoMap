<?php

namespace App\Services\Billing;

use App\Models\StripePlan;
use App\Models\User;
use App\Support\LocaleConfig;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Exceptions\InvalidCoupon;
use RuntimeException;

class SubscriptionCheckoutService
{
    public function checkout(User $user, StripePlan $plan, ?string $coupon = null): Checkout
    {
        if ($user->subscribed($plan->subscription_type)) {
            throw new RuntimeException('User already has an active subscription.');
        }

        $builder = $user->newSubscription($plan->subscription_type, $plan->stripe_price_id);

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
            'success_url' => $pricingUrl.'?checkout=success',
            'cancel_url' => $pricingUrl.'?checkout=cancelled',
        ]);
    }
}
