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

    /**
     * Pick a localized string from a bilingual pair stored in report/scoring payloads.
     *
     * @param  array{en?: string, fa?: string}  $pair
     */
    public static function pick(array $pair, ?string $locale = null): string
    {
        $locale = self::resolve($locale ?? app()->getLocale());

        if ($locale === 'fa') {
            return (string) ($pair['fa'] ?? $pair['en'] ?? '');
        }

        return (string) ($pair['en'] ?? $pair['fa'] ?? '');
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

    /**
     * Locale for the current request: URL segment, then session, then app default.
     */
    public static function active(): string
    {
        $routeLocale = request()->route('locale');

        if (is_string($routeLocale) && self::isSupported($routeLocale)) {
            return $routeLocale;
        }

        $sessionLocale = session('locale');

        if (is_string($sessionLocale) && self::isSupported($sessionLocale)) {
            return $sessionLocale;
        }

        return self::resolve(app()->getLocale());
    }

    /**
     * Locale for rendering UI copy: URL segment first, then session, then app locale.
     */
    public static function fromRoute(): string
    {
        return self::active();
    }

    /**
     * Route parameters with a guaranteed locale prefix for localized named routes.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public static function routeParameters(array $parameters = [], ?string $locale = null): array
    {
        return array_merge(
            ['locale' => self::resolve($locale ?? self::active())],
            $parameters,
        );
    }
}
