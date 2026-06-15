<?php

namespace Modules\VirtueEngine\Providers;

use Modules\VirtueEngine\Services\VirtueAIService;
use Modules\VirtueEngine\Services\VirtueApiService;
use Modules\VirtueEngine\Services\VirtueHabitService;
use Modules\VirtueEngine\Services\VirtueProgressService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class VirtueEngineServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'VirtueEngine';

    protected string $nameLower = 'virtueengine';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), 'virtue');

        $this->app->singleton(VirtueAIService::class);
        $this->app->singleton(VirtueApiService::class);
        $this->app->singleton(VirtueHabitService::class);
        $this->app->singleton(VirtueProgressService::class);
    }
}
