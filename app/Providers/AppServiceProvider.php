<?php

namespace App\Providers;

use App\Support\LocaleConfig;
use App\Support\TranslationBundle;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Translatable::fallback(
            fallbackLocale: LocaleConfig::fallback(),
            fallbackAny: false,
        );

        View::composer('layouts.app', function ($view): void {
            $view->with('i18nBundle', TranslationBundle::forGroups(['common', 'nav', 'home']));
        });
    }
}
