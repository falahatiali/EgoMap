<?php

namespace App\Services\Billing;

use App\Models\StripePlan;
use App\Models\User;
use App\Support\AdminSubscriptionListItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

class AdminSubscriptionListingService
{
    /**
     * @return LengthAwarePaginator<int, AdminSubscriptionListItem>
     */
    public function paginate(?string $search = null, string $statusFilter = 'active', int $perPage = 20): LengthAwarePaginator
    {
        $paginator = Subscription::query()
            ->with(['owner', 'items'])
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $term = '%'.$search.'%';

                $query->whereHas('owner', function ($ownerQuery) use ($term): void {
                    $ownerQuery->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($statusFilter === 'active', fn ($query) => $query->active())
            ->latest('created_at')
            ->paginate($perPage);

        $priceIds = $paginator->getCollection()
            ->map(fn (Subscription $subscription): ?string => $this->priceIdFor($subscription))
            ->filter()
            ->unique()
            ->values();

        /** @var Collection<string, StripePlan> $plansByPrice */
        $plansByPrice = StripePlan::query()
            ->whereIn('stripe_price_id', $priceIds)
            ->get()
            ->keyBy('stripe_price_id');

        $paginator->setCollection(
            $paginator->getCollection()->map(function (Subscription $subscription) use ($plansByPrice): AdminSubscriptionListItem {
                /** @var User $user */
                $user = $subscription->owner;

                $priceId = $this->priceIdFor($subscription);
                $plan = is_string($priceId) ? $plansByPrice->get($priceId) : null;
                $statusKey = (string) $subscription->stripe_status;

                return new AdminSubscriptionListItem(
                    subscription: $subscription,
                    user: $user,
                    planLabel: $plan?->billingPeriodName('en') ?? $priceId ?? __('admin.subscriptions.unknown_plan'),
                    statusLabel: $this->statusLabel($statusKey),
                    statusKey: $statusKey,
                );
            }),
        );

        return $paginator;
    }

    public function activeCount(): int
    {
        return Subscription::query()->active()->count();
    }

    private function priceIdFor(Subscription $subscription): ?string
    {
        $priceId = $subscription->stripe_price
            ?? $subscription->items->first()?->stripe_price;

        return is_string($priceId) && $priceId !== '' ? $priceId : null;
    }

    private function statusLabel(string $statusKey): string
    {
        $key = 'admin.subscriptions.statuses.'.$statusKey;
        $label = __($key);

        return $label === $key ? str_replace('_', ' ', $statusKey) : $label;
    }
}
