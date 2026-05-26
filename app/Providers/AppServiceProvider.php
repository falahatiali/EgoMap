<?php

namespace App\Providers;

use App\Listeners\ClaimGuestNoContactProtocols;
use App\Listeners\ClaimGuestQuizSessions;
use App\Listeners\SyncRecoveryJourneyOnLogin;
use App\Support\LocaleConfig;
use App\Support\TranslationBundle;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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

        View::composer(['layouts.app', 'layouts.guided', 'layouts.protocol'], function ($view): void {
            $view->with('i18nBundle', TranslationBundle::forGroups(['common', 'nav', 'home', 'landing', 'no_contact', 'recovery', 'profile']));
        });

        Event::listen(Login::class, ClaimGuestQuizSessions::class);
        Event::listen(Login::class, ClaimGuestNoContactProtocols::class);
        Event::listen(Login::class, SyncRecoveryJourneyOnLogin::class);
    }
}
