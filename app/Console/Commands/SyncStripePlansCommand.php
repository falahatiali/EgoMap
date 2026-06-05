<?php

namespace App\Console\Commands;

use App\Models\StripePlan;
use App\Services\Billing\StripeService;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'billing:sync-stripe-plans', description: 'Sync stripe_plans from Stripe prices')]
class SyncStripePlansCommand extends Command
{
    protected $signature = 'billing:sync-stripe-plans {--limit=100}';

    public function handle(StripeService $stripe): int
    {
        try {
            $prices = $stripe->all((int) $this->option('limit'));
        } catch (ApiErrorException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $synced = 0;

        foreach ($prices->data as $price) {
            StripePlan::query()->updateOrCreate(
                ['stripe_price_id' => $price->id],
                $this->attributesFromPrice($price),
            );

            $synced++;
        }

        $this->components->info("Synced {$synced} plan(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFromPrice(Price $price): array
    {
        $product = $price->product;
        $productId = is_string($product) ? $product : $product->id;

        return [
            'stripe_product_id' => $productId,
            'name' => ($product instanceof Product ? $product->name : null) ?? $price->nickname ?? 'Unnamed plan',
            'nickname' => $price->nickname,
            'description' => $product instanceof Product ? $product->description : null,
            'currency' => $price->currency,
            'unit_amount' => $price->unit_amount,
            'interval' => $price->recurring?->interval,
            'interval_count' => $price->recurring?->interval_count ?? 1,
            'billing_scheme' => $price->billing_scheme,
            'active' => $price->active,
            'is_recurring' => $price->type === 'recurring',
            'lookup_key' => $price->lookup_key,
            'synced_at' => now(),
        ];
    }
}
