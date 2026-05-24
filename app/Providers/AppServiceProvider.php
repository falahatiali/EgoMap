<?php

namespace App\Providers;

use App\Support\TranslationBundle;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.app', function ($view): void {
            $view->with('i18nBundle', TranslationBundle::forGroups(['common', 'nav', 'home']));
        });
    }
}
