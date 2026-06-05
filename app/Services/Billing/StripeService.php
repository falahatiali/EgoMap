<?php

namespace App\Services\Billing;

use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    private readonly StripeClient $client;

    public function __construct(?StripeClient $client = null)
    {
        $this->client = $client ?? new StripeClient([
            'api_key' => config('cashier.secret'),
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function all(int $limit): Collection
    {
        return $this->client->prices->all([
            'limit' => $limit,
            'active' => true,
            'expand' => ['data.product'],
        ]);
    }
}
