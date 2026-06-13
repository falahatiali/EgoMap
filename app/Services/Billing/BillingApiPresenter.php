<?php

namespace App\Services\Billing;

use App\Models\StripePlan;
use App\Models\User;
use App\Support\LocaleConfig;
use Illuminate\Support\Collection;

class BillingApiPresenter
{
    public function __construct(
        private readonly SubscriptionPlanResolver $planResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function catalogFor(User $user, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $currentPlan = $this->planResolver->currentPlanFor($user);

        /** @var Collection<int, StripePlan> $plans */
        $plans = StripePlan::query()
            ->where('active', true)
            ->where('is_recurring', true)
            ->orderedForDisplay()
            ->get();

        $monthlyPlan = $plans->first(fn (StripePlan $plan): bool => $plan->isMonthly());
        $yearlyPlan = $plans->first(fn (StripePlan $plan): bool => $plan->isYearly());
        $yearlySavingsPercent = $this->yearlySavingsPercent($monthlyPlan, $yearlyPlan);

        $planRelations = $plans->mapWithKeys(fn (StripePlan $plan): array => [
            $plan->id => $this->planResolver->planRelation($plan, $currentPlan),
        ]);

        $subscriptionType = (string) config('billing.subscription_name', 'default');

        return [
            'subscription' => [
                'active' => $user->hasActiveSubscription($subscriptionType),
                'is_pro' => $user->isPro(),
                'has_incomplete_payment' => $user->hasIncompletePayment($subscriptionType),
                'current_plan' => $currentPlan !== null
                    ? $this->presentPlan($currentPlan, $locale, 'current', $yearlySavingsPercent)
                    : null,
            ],
            'plans' => $plans
                ->map(fn (StripePlan $plan): array => $this->presentPlan(
                    $plan,
                    $locale,
                    $planRelations[$plan->id] ?? null,
                    $yearlySavingsPercent,
                ))
                ->values()
                ->all(),
            'yearly_savings_percent' => $yearlySavingsPercent,
            'pro_description' => $plans->first()?->description ?: __('pricing.pro_description', locale: $locale),
            'features' => [
                'free' => $this->featureList('free', 5, $locale),
                'pro' => $this->featureList('pro', 7, $locale),
            ],
            'labels' => $this->labels($locale, $currentPlan),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentCheckoutOutcome(PlanSelectionOutcome $outcome, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        $payload = [
            'outcome' => $outcome->type,
        ];

        if ($outcome->url !== null) {
            $payload['checkout_url'] = $outcome->url;
        }

        if ($outcome->message !== null) {
            $payload['message'] = $outcome->message;
        }

        $labels = match ($outcome->type) {
            PlanSelectionOutcome::Changed => [
                'title' => __('pricing.plan_changed_title', locale: $locale),
                'body' => __('pricing.plan_changed_body_generic', locale: $locale),
            ],
            PlanSelectionOutcome::Current => [
                'title' => __('pricing.error_current_plan', locale: $locale),
            ],
            PlanSelectionOutcome::Error => [
                'title' => $outcome->message ?? __('pricing.error_checkout_failed', locale: $locale),
            ],
            default => null,
        };

        if ($outcome->type === PlanSelectionOutcome::Current) {
            $payload['message'] = __('pricing.error_current_plan', locale: $locale);
        }

        if (is_array($labels)) {
            $payload['labels'] = $labels;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentConfirmResult(User $user, ?string $locale = null): array
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());
        $currentPlan = $this->planResolver->currentPlanFor($user);

        return [
            'confirmed' => true,
            'subscription' => [
                'active' => $user->hasActiveSubscription(),
                'is_pro' => $user->isPro(),
                'current_plan' => $currentPlan !== null
                    ? $this->presentPlan($currentPlan, $locale, 'current', null)
                    : null,
            ],
            'labels' => [
                'title' => __('pricing.checkout_success_title', locale: $locale),
                'body' => $currentPlan !== null
                    ? __('pricing.checkout_success_with_plan', [
                        'plan' => $currentPlan->billingPeriodName($locale),
                    ], locale: $locale)
                    : __('pricing.checkout_success_body', locale: $locale),
            ],
        ];
    }

    public static function mobileCheckoutReturnUrls(): CheckoutReturnUrls
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        $successUrl = (string) config(
            'billing.mobile_checkout_success_url',
            $appUrl.'/billing/app-return?checkout=success&session_id={CHECKOUT_SESSION_ID}',
        );

        $cancelUrl = (string) config(
            'billing.mobile_checkout_cancel_url',
            $appUrl.'/billing/app-return?checkout=cancelled',
        );

        return new CheckoutReturnUrls($successUrl, $cancelUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPlan(
        StripePlan $plan,
        string $locale,
        ?string $relation,
        ?int $yearlySavingsPercent,
    ): array {
        $badge = match (true) {
            $relation === 'current' => 'current',
            $plan->isQuarterly() => 'popular',
            $plan->isYearly() && $yearlySavingsPercent !== null => 'best_value',
            default => null,
        };

        return [
            'id' => $plan->id,
            'billing_period' => $plan->billingPeriod(),
            'name' => $plan->billingPeriodName($locale),
            'description' => $plan->description,
            'price' => [
                'formatted' => $plan->formattedPrice($locale),
                'unit_amount' => $plan->unit_amount,
                'currency' => strtoupper((string) $plan->currency),
            ],
            'cadence_label' => $plan->billingCadenceLabel($locale),
            'relation' => $relation,
            'badge' => $badge,
            'savings_percent' => $plan->isYearly() ? $yearlySavingsPercent : null,
            'savings_label' => ($plan->isYearly() && $yearlySavingsPercent !== null)
                ? __('pricing.save_percent', ['percent' => $yearlySavingsPercent], locale: $locale)
                : null,
            'cta_label' => $this->ctaLabel($relation, $locale),
            'selectable' => $relation !== 'current',
        ];
    }

    private function ctaLabel(?string $relation, string $locale): string
    {
        return match ($relation) {
            'current' => __('pricing.current_plan', locale: $locale),
            'upgrade' => __('pricing.upgrade_plan', locale: $locale),
            'downgrade' => __('pricing.downgrade_plan', locale: $locale),
            default => __('pricing.pro_cta', locale: $locale),
        };
    }

    /**
     * @return list<string>
     */
    private function featureList(string $tier, int $count, string $locale): array
    {
        $features = [];

        for ($index = 1; $index <= $count; $index++) {
            $features[] = __("pricing.{$tier}_feature_{$index}", locale: $locale);
        }

        return $features;
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $locale, ?StripePlan $currentPlan): array
    {
        return [
            'page_title' => __('pricing.page_title', locale: $locale),
            'hero_title' => __('pricing.hero_title', locale: $locale),
            'hero_subtitle' => __('pricing.hero_subtitle', locale: $locale),
            'free_title' => __('pricing.free_title', locale: $locale),
            'free_price' => __('pricing.free_price', locale: $locale),
            'free_interval' => __('pricing.free_interval', locale: $locale),
            'pro_title' => __('pricing.pro_title', locale: $locale),
            'already_pro' => __('pricing.already_pro', locale: $locale),
            'already_subscribed' => __('pricing.already_subscribed', locale: $locale),
            'active_plan' => $currentPlan !== null
                ? __('pricing.active_plan', ['plan' => $currentPlan->billingPeriodName($locale)], locale: $locale)
                : null,
            'compare_title' => __('pricing.compare_title', locale: $locale),
            'compare_subtitle' => __('pricing.compare_subtitle', locale: $locale),
            'empty_plans' => __('pricing.empty_plans', locale: $locale),
            'secure_checkout' => __('pricing.secure_checkout', locale: $locale),
            'checkout_cancelled' => __('pricing.checkout_cancelled', locale: $locale),
            'current_plan_badge' => __('pricing.current_plan_badge', locale: $locale),
            'popular_badge' => __('pricing.pro_badge', locale: $locale),
            'best_value_badge' => __('pricing.yearly_badge', locale: $locale),
            'return_to_app_hint' => __('pricing.mobile_return_hint', locale: $locale),
        ];
    }

    private function yearlySavingsPercent(?StripePlan $monthly, ?StripePlan $yearly): ?int
    {
        if ($monthly === null || $yearly === null || $monthly->unit_amount === null || $yearly->unit_amount === null) {
            return null;
        }

        $monthlyAnnualized = $monthly->unit_amount * 12;

        if ($monthlyAnnualized <= $yearly->unit_amount) {
            return null;
        }

        return (int) round((1 - ($yearly->unit_amount / $monthlyAnnualized)) * 100);
    }
}
