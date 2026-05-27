<?php

use App\Support\LocalizedNumbers;

if (! function_exists('eg_num')) {
    /**
     * Locale-aware digits for display (Persian numerals when locale is fa).
     */
    function eg_num(int|float|string $value, ?string $locale = null): string
    {
        return LocalizedNumbers::format($value, $locale);
    }
}

if (! function_exists('eg_num_pct')) {
    function eg_num_pct(int|float $value, ?string $locale = null): string
    {
        return LocalizedNumbers::percent($value, $locale);
    }
}
