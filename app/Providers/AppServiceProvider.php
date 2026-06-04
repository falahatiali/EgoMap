<?php

namespace App\Providers;

use App\Listeners\ClaimGuestNoContactProtocols;
use App\Listeners\ClaimGuestQuizSessions;
use App\Listeners\SyncRecoveryJourneyOnLogin;
use App\Services\Pdf\Drivers\RtlAwareDomPdfDriver;
use App\Support\LocaleConfig;
use App\Support\TranslationBundle;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Middleware\Authenticate;
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
        // Ensure global helper functions (eg_num / eg_num_pct) are available in Blade.
        // We intentionally require it here (runtime) so Blade doesn't fail when composer autoload "files"
        // hasn't been rebuilt yet.
        $helpersPath = app_path('helpers.php');

        if (is_file($helpersPath)) {
            require_once $helpersPath;
        }

        $this->app->singleton('laravel-pdf.driver.dompdf', function () {
            return new RtlAwareDomPdfDriver(config('laravel-pdf.dompdf', []));
        });
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

        Authenticate::redirectUsing(function (): string {
            $locale = session('locale');

            if (! is_string($locale) || ! LocaleConfig::isSupported($locale)) {
                $locale = LocaleConfig::default();
            }

            return route('login', ['locale' => $locale]);
        });

        View::composer(['layouts.app', 'layouts.guided', 'layouts.protocol'], function ($view): void {
            $view->with('i18nBundle', TranslationBundle::forGroups(['common', 'nav', 'home', 'landing', 'no_contact', 'recovery', 'profile', 'missions']));
        });

        Event::listen(Login::class, ClaimGuestQuizSessions::class);
        Event::listen(Login::class, ClaimGuestNoContactProtocols::class);
        Event::listen(Login::class, SyncRecoveryJourneyOnLogin::class);
    }
}
