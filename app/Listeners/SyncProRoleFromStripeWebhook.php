<?php

namespace App\Listeners;

use App\Services\Billing\ProSubscriptionService;
use Laravel\Cashier\Events\WebhookHandled;

class SyncProRoleFromStripeWebhook
{
    public function __construct(
        private readonly ProSubscriptionService $proSubscriptions,
    ) {}

    public function handle(WebhookHandled $event): void
    {
        $this->proSubscriptions->syncFromWebhookPayload($event->payload);
    }
}
