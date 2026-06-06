<?php

namespace App\Listeners;

use App\Support\LocaleConfig;
use Illuminate\Auth\Events\Login;

class RedirectToIntendedPricingPlan
{
    public function handle(Login $event): void
    {
        $planId = session('pricing_intended_plan_id');

        if (! is_numeric($planId)) {
            return;
        }

        $locale = session('locale');
        $locale = is_string($locale) && LocaleConfig::isSupported($locale)
            ? $locale
            : LocaleConfig::default();

        session()->put(
            'url.intended',
            route('pricing', ['locale' => $locale]).'?resume_plan='.(int) $planId,
        );
    }
}
