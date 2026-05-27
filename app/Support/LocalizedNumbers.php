<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Locale-aware digit formatting (Western 0-9 for en, Persian ۰-۹ for fa).
 */
final class LocalizedNumbers
{
    /** @var array<string, string> */
    private const TO_PERSIAN = [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹',
        '%' => '٪',
    ];

    public static function usesPersianDigits(?string $locale = null): bool
    {
        return LocaleConfig::resolve($locale ?? app()->getLocale()) === 'fa';
    }

    /**
     * Format a number or replace ASCII digits (and %) inside a string.
     */
    public static function format(int|float|string $value, ?string $locale = null): string
    {
        $string = match (true) {
            is_int($value) => (string) $value,
            is_float($value) => rtrim(rtrim(sprintf('%.2f', $value), '0'), '.'),
            default => (string) $value,
        };

        if (! self::usesPersianDigits($locale)) {
            return $string;
        }

        return strtr($string, self::TO_PERSIAN);
    }

    public static function percent(int|float $value, ?string $locale = null): string
    {
        $rounded = (string) (int) round($value);

        return self::format($rounded, $locale).(self::usesPersianDigits($locale) ? '٪' : '%');
    }

    public static function formatDate(CarbonInterface $date, string $format, ?string $locale = null): string
    {
        $locale = LocaleConfig::resolve($locale ?? app()->getLocale());

        return self::format($date->locale($locale)->translatedFormat($format), $locale);
    }
}
