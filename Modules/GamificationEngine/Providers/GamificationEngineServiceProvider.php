<?php

namespace Modules\GamificationEngine\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\GamificationEngine\Services\GamificationAnalyticsService;
use Modules\GamificationEngine\Services\GamificationCatalogService;
use Modules\GamificationEngine\Services\GamificationEngine;
use Modules\GamificationEngine\Services\GamificationRuleMatcher;
use Modules\GamificationEngine\Services\GamificationWalletResolver;
use Modules\GamificationEngine\Support\GamificationMetadataEnricher;
use Modules\GamificationEngine\Support\GamificationSchemaSync;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Registers gamification singletons: engine, wallet resolver, matcher, analytics, catalog.
 */
class GamificationEngineServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        GamificationSchemaSync::ensurePerksTable();
    }

    /** Bind module services into the container. */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(GamificationRuleMatcher::class);
        $this->app->singleton(GamificationMetadataEnricher::class);
        $this->app->singleton(GamificationWalletResolver::class);
        $this->app->singleton(GamificationAnalyticsService::class);
        $this->app->singleton(GamificationCatalogService::class);
        $this->app->singleton(GamificationEngine::class);
    }

    /**
     * The name of the module.
     */
    protected string $name = 'GamificationEngine';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'gamificationengine';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
