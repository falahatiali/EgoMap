<?php

namespace App\Services\Billing;

final readonly class CheckoutReturnUrls
{
    public function __construct(
        public string $successUrl,
        public string $cancelUrl,
    ) {}

    /**
     * @return array{success_url: string, cancel_url: string}
     */
    public function toStripeOptions(): array
    {
        return [
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
        ];
    }
}
