<?php

namespace App\Services\Locale;

use NumberFormatter;

class LocaleDigitFormatter
{
    /**
     * @var array<string, string>
     */
    private const PERSIAN_DIGITS = [
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
    ];

    public function usesLocalizedDigits(?string $locale = null): bool
    {
        return str_starts_with($locale ?? app()->getLocale(), 'fa');
    }

    public function format(int|float|string $value, ?string $locale = null): string
    {
        if (! $this->usesLocalizedDigits($locale)) {
            return (string) $value;
        }

        if (extension_loaded('intl')) {
            $formatter = new NumberFormatter($this->numberFormatterLocale($locale), NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
            $formatted = $formatter->format((float) $value);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $this->convertWesternDigitsInString((string) $value);
    }

    public function pad(int $value, int $length = 2, ?string $locale = null): string
    {
        $western = str_pad((string) $value, $length, '0', STR_PAD_LEFT);

        if (! $this->usesLocalizedDigits($locale)) {
            return $western;
        }

        return $this->convertWesternDigitsInString($western, $locale);
    }

    public function convertWesternDigitsInString(string $value, ?string $locale = null): string
    {
        if (! $this->usesLocalizedDigits($locale)) {
            return $value;
        }

        return strtr($value, self::PERSIAN_DIGITS);
    }

    private function numberFormatterLocale(?string $locale): string
    {
        return str_starts_with($locale ?? app()->getLocale(), 'fa') ? 'fa_IR' : 'en_US';
    }
}
