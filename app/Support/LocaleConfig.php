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
     * Pick a localized string from a map keyed by locale code (en, fa, …).
     *
     * @param  array<string, string|null>  $map
     */
    public static function pick(array $map, ?string $locale = null): string
    {
        if ($locale !== null && isset($map[$locale]) && is_string($map[$locale]) && $map[$locale] !== '') {
            return $map[$locale];
        }

        $locale = self::resolve($locale ?? app()->getLocale());

        if (isset($map[$locale]) && is_string($map[$locale]) && $map[$locale] !== '') {
            return $map[$locale];
        }

        $fallback = self::fallback();

        if (isset($map[$fallback]) && is_string($map[$fallback]) && $map[$fallback] !== '') {
            return $map[$fallback];
        }

        foreach (self::supported() as $code) {
            if (isset($map[$code]) && is_string($map[$code]) && $map[$code] !== '') {
                return $map[$code];
            }
        }

        return '';
    }

    /**
     * Translate a lang key for a specific locale with fallback.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function translate(string $key, ?string $locale = null, array $replace = []): string
    {
        $locale = self::resolve($locale ?? app()->getLocale());
        $line = trans($key, $replace, $locale);

        if (is_string($line) && $line !== $key) {
            return $line;
        }

        $fallback = self::fallback();

        if ($fallback !== $locale) {
            $line = trans($key, $replace, $fallback);

            if (is_string($line) && $line !== $key) {
                return $line;
            }
        }

        return is_string($line) ? $line : $key;
    }

    /**
     * @return list<string>
     */
    public static function translateLines(string $key, ?string $locale = null): array
    {
        $locale = self::resolve($locale ?? app()->getLocale());
        $lines = trans($key, [], $locale);

        if (is_array($lines)) {
            return array_values(array_filter(array_map(
                static fn (mixed $line): string => is_string($line) ? $line : '',
                $lines,
            )));
        }

        $fallback = self::fallback();

        if ($fallback !== $locale) {
            $lines = trans($key, [], $fallback);

            if (is_array($lines)) {
                return array_values(array_filter(array_map(
                    static fn (mixed $line): string => is_string($line) ? $line : '',
                    $lines,
                )));
            }
        }

        return [];
    }

    public static function aiLanguageName(?string $locale = null): string
    {
        $locale = self::resolve($locale ?? app()->getLocale());

        /** @var array<string, string> $names */
        $names = config('locales.ai_language_names', []);

        if (isset($names[$locale]) && $names[$locale] !== '') {
            return $names[$locale];
        }

        return strtoupper($locale);
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
