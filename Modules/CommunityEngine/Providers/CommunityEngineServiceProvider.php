<?php

namespace Modules\CommunityEngine\Providers;

use Modules\CommunityEngine\Services\CommunityCommentService;
use Modules\CommunityEngine\Services\CommunityModerationService;
use Modules\CommunityEngine\Services\CommunityPostService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CommunityEngineServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'CommunityEngine';

    protected string $nameLower = 'communityengine';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), 'community');

        $this->app->singleton(CommunityModerationService::class);
        $this->app->singleton(CommunityPostService::class);
        $this->app->singleton(CommunityCommentService::class);
    }
}
