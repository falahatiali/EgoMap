<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

final class LocaleUrl
{
    /**
     * Same path as the current request (or an explicit path), with the locale prefix swapped.
     */
    public static function switch(string $locale, ?string $path = null): string
    {
        if (! LocaleConfig::isSupported($locale)) {
            $locale = LocaleConfig::default();
        }

        $segments = $path !== null
            ? array_values(array_filter(explode('/', trim($path, '/'))))
            : request()->segments();

        if (isset($segments[0]) && LocaleConfig::isSupported($segments[0])) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $built = '/'.implode('/', $segments);

        $query = $path === null ? request()->getQueryString() : null;

        if ($query) {
            $built .= '?'.$query;
        }

        return url($built);
    }

    public static function switchFromReferer(string $locale, ?string $referer): string
    {
        if (! is_string($referer) || $referer === '') {
            return self::home($locale);
        }

        $path = parse_url($referer, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || $path === '/') {
            return self::home($locale);
        }

        return self::switch($locale, $path);
    }

    /**
     * Home path for a locale (e.g. /en, /fa).
     */
    public static function home(string $locale): string
    {
        return url('/'.LocaleConfig::resolve($locale));
    }

    public static function applyRouteDefaults(?string $locale = null): void
    {
        URL::defaults(['locale' => LocaleConfig::resolve($locale ?? app()->getLocale())]);
    }
}
