<?php

namespace App\Http\Middleware;

use App\Support\LocaleConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', LocaleConfig::default());

        if (is_string($locale)) {
            $locale = strtolower(substr($locale, 0, 2));

            if (LocaleConfig::isSupported($locale)) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }
}
