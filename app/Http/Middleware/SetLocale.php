<?php

namespace App\Http\Middleware;

use App\Support\LocaleConfig;
use App\Support\LocaleUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');

        if (is_string($routeLocale) && LocaleConfig::isSupported($routeLocale)) {
            session(['locale' => $routeLocale]);
            App::setLocale($routeLocale);
        } else {
            App::setLocale(LocaleConfig::resolve(session('locale')));
        }

        LocaleUrl::applyRouteDefaults();

        return $next($request);
    }
}
