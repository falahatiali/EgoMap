<?php

namespace App\Support;

use App\Models\User;
use App\Services\Billing\SubscriptionPlanResolver;

class NavProfilePresenter
{
    public function __construct(
        private readonly SubscriptionPlanResolver $planResolver,
    ) {}

    public function forUser(?User $user): ?NavProfileData
    {
        if ($user === null) {
            return null;
        }

        $name = trim($user->name);

        if ($name === '') {
            $name = __('nav.member');
        }

        $plan = $this->planResolver->currentPlanFor($user);
        $planBadge = null;
        $planPeriod = null;

        if ($plan !== null) {
            $planBadge = $plan->billingPeriodName();
            $planPeriod = $plan->billingPeriod();
        } elseif ($user->hasActiveSubscription()) {
            $planBadge = __('nav.pro_badge');
            $planPeriod = 'pro';
        }

        return new NavProfileData(
            name: $name,
            initial: mb_strtoupper(mb_substr($name, 0, 1)),
            planBadge: $planBadge,
            planPeriod: $planPeriod,
        );
    }
}
