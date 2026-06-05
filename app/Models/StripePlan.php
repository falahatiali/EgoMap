<?php

namespace App\Models;

use Database\Factories\StripePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

#[Fillable([
    'stripe_price_id',
    'stripe_product_id',
    'name',
    'nickname',
    'description',
    'currency',
    'unit_amount',
    'interval',
    'interval_count',
    'billing_scheme',
    'active',
    'is_recurring',
    'lookup_key',
    'subscription_type',
    'metadata',
    'synced_at',
])]
class StripePlan extends Model
{
    /** @use HasFactory<StripePlanFactory> */
    use HasFactory;

    /**
     * Stripe amounts with no minor unit (e.g. JPY).
     *
     * @var list<string>
     */
    private const array ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_amount' => 'integer',
            'interval_count' => 'integer',
            'active' => 'boolean',
            'is_recurring' => 'boolean',
            'metadata' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Major currency amount for display (e.g. 6999 cents → 69.99 USD).
     */
    public function unitAmountMajor(): ?float
    {
        if ($this->unit_amount === null) {
            return null;
        }

        return $this->unit_amount / $this->currencySubunit();
    }

    /**
     * Locale-aware price label (e.g. "$69.99").
     */
    public function formattedPrice(?string $locale = null): ?string
    {
        $major = $this->unitAmountMajor();

        if ($major === null) {
            return null;
        }

        return Number::currency(
            $major,
            in: strtoupper($this->currency),
            locale: $locale ?? app()->getLocale(),
        );
    }

    private function currencySubunit(): int
    {
        return in_array(strtolower($this->currency), self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;
    }
}
