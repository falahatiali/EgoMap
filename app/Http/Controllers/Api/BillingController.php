<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StripePlan;
use App\Services\Billing\BillingApiPresenter;
use App\Services\Billing\CheckoutReturnSyncService;
use App\Services\Billing\PlanSelectionOutcome;
use App\Services\Billing\SubscriptionCheckoutService;
use App\Support\LocaleConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function show(Request $request, BillingApiPresenter $presenter): JsonResponse
    {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $locale = LocaleConfig::resolve(app()->getLocale());

        return response()->json($presenter->catalogFor($user, $locale));
    }

    public function checkout(
        Request $request,
        BillingApiPresenter $presenter,
        SubscriptionCheckoutService $checkout,
    ): JsonResponse {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:stripe_plans,id'],
            'coupon' => ['nullable', 'string', 'max:64'],
        ]);

        $plan = StripePlan::query()
            ->where('active', true)
            ->where('is_recurring', true)
            ->findOrFail($validated['plan_id']);

        $coupon = isset($validated['coupon']) && is_string($validated['coupon']) && $validated['coupon'] !== ''
            ? $validated['coupon']
            : null;

        $outcome = $checkout->selectPlan(
            $user,
            $plan,
            $coupon,
            BillingApiPresenter::mobileCheckoutReturnUrls(),
        );

        $locale = LocaleConfig::resolve(app()->getLocale());
        $status = match ($outcome->type) {
            PlanSelectionOutcome::Error => 422,
            PlanSelectionOutcome::Current => 409,
            default => 200,
        };

        return response()->json(
            $presenter->presentCheckoutOutcome($outcome, $locale),
            $status,
        );
    }

    public function confirmCheckout(
        Request $request,
        BillingApiPresenter $presenter,
        CheckoutReturnSyncService $checkoutReturnSync,
    ): JsonResponse {
        $user = $request->user('sanctum');
        abort_unless($user !== null, 401);

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);

        $checkoutReturnSync->syncFromCheckoutSession($user, $validated['session_id']);

        $locale = LocaleConfig::resolve(app()->getLocale());

        return response()->json(
            $presenter->presentConfirmResult($user->fresh(), $locale),
        );
    }
}
