<?php

namespace Modules\AetherEngine\Providers;

use Modules\AetherEngine\Contracts\NutritionGeneratorInterface;
use Modules\AetherEngine\Contracts\ProgramEnrichmentInterface;
use Modules\AetherEngine\Contracts\ScheduleOptimizerInterface;
use Modules\AetherEngine\Contracts\WorkoutGeneratorInterface;
use Modules\AetherEngine\Services\AetherEngineService;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\AetherEngine\Services\AetherProgramPersistenceService;
use Modules\AetherEngine\Services\AetherPromptBuilder;
use Modules\AetherEngine\Services\ExerciseLibrary;
use Modules\AetherEngine\Services\InjuryTagResolver;
use Modules\AetherEngine\Services\MealTemplateLibrary;
use Modules\AetherEngine\Services\MetabolicCalculator;
use Modules\AetherEngine\Services\NutritionGenerator;
use Modules\AetherEngine\Services\ProgramEnrichmentService;
use Modules\AetherEngine\Services\ScheduleOptimizer;
use Modules\AetherEngine\Services\WorkoutGenerator;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AetherEngineServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AetherEngine';

    protected string $nameLower = 'aetherengine';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), 'aether');

        $this->app->singleton(InjuryTagResolver::class);
        $this->app->singleton(ExerciseLibrary::class);
        $this->app->singleton(MealTemplateLibrary::class);
        $this->app->singleton(MetabolicCalculator::class);
        $this->app->singleton(AetherPromptBuilder::class);

        $this->app->singleton(WorkoutGeneratorInterface::class, WorkoutGenerator::class);
        $this->app->singleton(NutritionGeneratorInterface::class, NutritionGenerator::class);
        $this->app->singleton(ScheduleOptimizerInterface::class, ScheduleOptimizer::class);
        $this->app->singleton(ProgramEnrichmentInterface::class, ProgramEnrichmentService::class);

        $this->app->singleton(WorkoutGenerator::class);
        $this->app->singleton(NutritionGenerator::class);
        $this->app->singleton(ScheduleOptimizer::class);
        $this->app->singleton(ProgramEnrichmentService::class);

        $this->app->singleton(AetherProgramPersistenceService::class);
        $this->app->singleton(AetherProfileService::class);
        $this->app->singleton(AetherEngineService::class);
    }
}
