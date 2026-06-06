<?php

namespace App\Support;

use App\Models\User;
use Laravel\Cashier\Subscription;

final readonly class AdminSubscriptionListItem
{
    public function __construct(
        public Subscription $subscription,
        public User $user,
        public string $planLabel,
        public string $statusLabel,
        public string $statusKey,
    ) {}
}
