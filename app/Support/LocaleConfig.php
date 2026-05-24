<?php

namespace App\Support;

final class LocaleConfig
{
    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        /** @var list<string> $locales */
        $locales = config('locales.supported', ['en', 'fa']);

        return $locales;
    }

    public static function default(): string
    {
        return (string) config('locales.default', 'en');
    }

    public static function fallback(): string
    {
        return (string) config('locales.fallback', 'en');
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }

    public static function isRtl(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        /** @var list<string> $rtl */
        $rtl = config('locales.rtl', ['fa']);

        return in_array($locale, $rtl, true);
    }

    /**
     * Normalize session/request locale to a supported value.
     */
    public static function resolve(?string $locale): string
    {
        if ($locale !== null && self::isSupported($locale)) {
            return $locale;
        }

        return self::default();
    }
}
