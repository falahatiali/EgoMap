<?php

namespace App\Livewire\Pricing;

use App\Models\StripePlan;
use App\Models\User;
use App\Services\Billing\CheckoutReturnSyncService;
use App\Services\Billing\PlanSelectionOutcome;
use App\Services\Billing\SubscriptionCheckoutService;
use App\Services\Billing\SubscriptionPlanResolver;
use App\Support\LocaleConfig;
use App\Support\RebootProtocolQuiz;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public ?string $coupon = null;

    public bool $checkoutSuccess = false;

    public bool $checkoutCancelled = false;

    public bool $planChanged = false;

    public ?string $subscribeError = null;

    public function mount(CheckoutReturnSyncService $checkoutReturnSync): void
    {
        $coupon = request()->query('coupon');

        if (is_string($coupon) && $coupon !== '') {
            $this->coupon = $coupon;
        }

        $this->checkoutSuccess = request()->query('checkout') === 'success';
        $this->checkoutCancelled = request()->query('checkout') === 'cancelled';

        $user = auth()->user();

        if ($this->checkoutSuccess && $user !== null) {
            $sessionId = request()->query('session_id');

            if (is_string($sessionId) && $sessionId !== '') {
                $checkoutReturnSync->syncFromCheckoutSession($user, $sessionId);
            }
        }

        $resumePlan = request()->query('resume_plan');

        if (is_numeric($resumePlan) && $user !== null) {
            session()->forget('pricing_intended_plan_id');

            if (! $user->hasActiveSubscription()) {
                $this->subscribe((int) $resumePlan, app(SubscriptionCheckoutService::class));
            }
        }
    }

    public function subscribe(
        int $planId,
        SubscriptionCheckoutService $checkout,
    ): mixed {
        $this->subscribeError = null;
        $this->planChanged = false;

        /** @var User $user */
        $user = auth()->user();

        if ($user === null) {
            session(['pricing_intended_plan_id' => $planId]);

            return $this->redirect(route('login', LocaleConfig::routeParameters()), navigate: true);
        }

        $plan = StripePlan::query()
            ->where('active', true)
            ->findOrFail($planId);

        $outcome = $checkout->selectPlan($user, $plan, $this->coupon);

        return $this->applyPlanSelectionOutcome($outcome);
    }

    private function applyPlanSelectionOutcome(PlanSelectionOutcome $outcome): mixed
    {
        return match ($outcome->type) {
            PlanSelectionOutcome::Redirect => $this->redirect($outcome->url),
            PlanSelectionOutcome::Changed => tap(null, function (): void {
                $this->planChanged = true;
                $this->dispatch('pricing-plan-changed');
            }),
            PlanSelectionOutcome::Current => tap(null, function (): void {
                $this->subscribeError = __('pricing.error_current_plan');
                $this->dispatch('pricing-current-plan');
            }),
            PlanSelectionOutcome::Error => tap(null, function () use ($outcome): void {
                $this->subscribeError = $outcome->message ?? __('pricing.error_checkout_failed');
            }),
            default => null,
        };
    }

    public function render(): View
    {
        $locale = app()->getLocale();
        $user = auth()->user();
        $planResolver = app(SubscriptionPlanResolver::class);

        /** @var Collection<int, StripePlan> $plans */
        $plans = StripePlan::query()
            ->where('active', true)
            ->where('is_recurring', true)
            ->orderedForDisplay()
            ->get();

        $monthlyPlan = $plans->first(fn (StripePlan $plan): bool => $plan->isMonthly());
        $yearlyPlan = $plans->first(fn (StripePlan $plan): bool => $plan->isYearly());
        $currentPlan = $user !== null ? $planResolver->currentPlanFor($user) : null;

        $planRelations = $plans->mapWithKeys(fn (StripePlan $plan): array => [
            $plan->id => $planResolver->planRelation($plan, $currentPlan),
        ]);

        return view('livewire.pricing.show', [
            'locale' => $locale,
            'plans' => $plans,
            'proDescription' => $plans->first()?->description ?: __('pricing.pro_description'),
            'yearlySavingsPercent' => $this->yearlySavingsPercent($monthlyPlan, $yearlyPlan),
            'hasActiveSubscription' => $user?->hasActiveSubscription() ?? false,
            'currentPlan' => $currentPlan,
            'planRelations' => $planRelations,
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
