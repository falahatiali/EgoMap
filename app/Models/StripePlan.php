<?php

namespace App\Models;

use Database\Factories\StripePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
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

    /**
     * @param  Builder<StripePlan>  $query
     * @return Builder<StripePlan>
     */
    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE
                WHEN `interval` = 'month' AND `interval_count` = 1 THEN 0
                WHEN `interval` = 'month' AND `interval_count` = 3 THEN 1
                WHEN `interval` = 'year' THEN 2
                ELSE 3
            END
        ")->orderBy('unit_amount');
    }

    public function billingPeriod(): string
    {
        if ($this->interval === 'year') {
            return 'yearly';
        }

        if ($this->interval === 'month' && $this->interval_count === 3) {
            return 'quarterly';
        }

        if ($this->interval === 'month' && $this->interval_count === 1) {
            return 'monthly';
        }

        return 'other';
    }

    public function billingPeriodName(?string $locale = null): string
    {
        $key = match ($this->billingPeriod()) {
            'monthly' => 'pricing.period_monthly',
            'quarterly' => 'pricing.period_quarterly',
            'yearly' => 'pricing.period_yearly',
            default => null,
        };

        if ($key === null) {
            return $this->name;
        }

        return Lang::get($key, [], $locale ?? app()->getLocale());
    }

    public function billingCadenceLabel(?string $locale = null): string
    {
        $key = match ($this->billingPeriod()) {
            'monthly' => 'pricing.cadence_monthly',
            'quarterly' => 'pricing.cadence_quarterly',
            'yearly' => 'pricing.cadence_yearly',
            default => 'pricing.cadence_other',
        };

        return Lang::get($key, [
            'count' => $this->interval_count,
            'interval' => $this->interval,
        ], $locale ?? app()->getLocale());
    }

    public function isMonthly(): bool
    {
        return $this->billingPeriod() === 'monthly';
    }

    public function isQuarterly(): bool
    {
        return $this->billingPeriod() === 'quarterly';
    }

    public function isYearly(): bool
    {
        return $this->billingPeriod() === 'yearly';
    }

    private function currencySubunit(): int
    {
        return in_array(strtolower($this->currency), self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;
    }
}
