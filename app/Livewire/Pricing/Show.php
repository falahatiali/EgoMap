<?php

namespace App\Livewire\Pricing;

use App\Models\StripePlan;
use App\Services\Billing\SubscriptionCheckoutService;
use App\Support\LocaleConfig;
use App\Support\RebootProtocolQuiz;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
class Show extends Component
{
    public ?string $coupon = null;

    public bool $checkoutSuccess = false;

    public bool $checkoutCancelled = false;

    public function mount(): void
    {
        $coupon = request()->query('coupon');

        if (is_string($coupon) && $coupon !== '') {
            $this->coupon = $coupon;
        }

        $this->checkoutSuccess = request()->query('checkout') === 'success';
        $this->checkoutCancelled = request()->query('checkout') === 'cancelled';
    }

    public function subscribe(int $planId, SubscriptionCheckoutService $checkout): mixed
    {
        $user = auth()->user();

        if ($user === null) {
            session(['pricing_intended_plan_id' => $planId]);

            return $this->redirect(route('login', LocaleConfig::routeParameters()), navigate: true);
        }

        if ($user->hasActiveSubscription()) {
            $this->dispatch('pricing-already-subscribed');

            return null;
        }

        $plan = StripePlan::query()
            ->where('active', true)
            ->findOrFail($planId);

        try {
            return $checkout->checkout($user, $plan, $this->coupon);
        } catch (RuntimeException) {
            $this->dispatch('pricing-already-pro');

            return null;
        }
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        /** @var Collection<int, StripePlan> $plans */
        $plans = StripePlan::query()
            ->where('active', true)
            ->where('is_recurring', true)
            ->orderedForDisplay()
            ->get();

        $monthlyPlan = $plans->first(fn (StripePlan $plan): bool => $plan->isMonthly());
        $yearlyPlan = $plans->first(fn (StripePlan $plan): bool => $plan->isYearly());

        return view('livewire.pricing.show', [
            'locale' => $locale,
            'plans' => $plans,
            'proDescription' => $plans->first()?->description ?: __('pricing.pro_description'),
            'yearlySavingsPercent' => $this->yearlySavingsPercent($monthlyPlan, $yearlyPlan),
            'hasActiveSubscription' => auth()->user()?->hasActiveSubscription() ?? false,
            'quizStartUrl' => route('quiz.start', [
                'slug' => RebootProtocolQuiz::SLUG,
                'locale' => $locale,
            ]),
        ]);
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
