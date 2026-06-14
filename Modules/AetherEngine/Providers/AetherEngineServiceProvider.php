<?php

namespace Modules\AetherEngine\Providers;

use Modules\AetherEngine\Contracts\NutritionGeneratorInterface;
use Modules\AetherEngine\Contracts\ProgramEnrichmentInterface;
use Modules\AetherEngine\Contracts\ScheduleOptimizerInterface;
use Modules\AetherEngine\Contracts\WorkoutGeneratorInterface;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbApi;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbExerciseCatalogProvider;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbExerciseIndex;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbExerciseMediaProvider;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbHttpClient;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbResponseMapper;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiExerciseCatalogProvider;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiExerciseMediaProvider;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiHttpClient;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiResponseMapper;
use Modules\AetherEngine\Integrations\WorkoutX\WorkoutXExerciseMediaProvider;
use Modules\AetherEngine\Integrations\WorkoutX\WorkoutXHttpClient;
use Modules\AetherEngine\Services\AetherAiGenerationRecorder;
use Modules\AetherEngine\Services\AetherEngineService;
use Modules\AetherEngine\Services\AetherProfileService;
use Modules\AetherEngine\Services\AetherProgramEditEventService;
use Modules\AetherEngine\Services\AetherProgramPersistenceService;
use Modules\AetherEngine\Services\AetherPromptBuilder;
use Modules\AetherEngine\Services\AetherWorkoutLogService;
use Modules\AetherEngine\Services\AetherWorkoutSessionService;
use Modules\AetherEngine\Services\ExerciseCatalog\ExerciseCatalogProviderRegistry;
use Modules\AetherEngine\Services\ExerciseCatalog\ExerciseCatalogService;
use Modules\AetherEngine\Services\ExerciseLibrary;
use Modules\AetherEngine\Services\ExerciseMedia\ExerciseMediaProviderRegistry;
use Modules\AetherEngine\Services\ExerciseMedia\ExerciseMediaResolver;
use Modules\AetherEngine\Services\ExerciseSubstitutionService;
use Modules\AetherEngine\Services\InjuryTagResolver;
use Modules\AetherEngine\Services\MealTemplateLibrary;
use Modules\AetherEngine\Services\MetabolicCalculator;
use Modules\AetherEngine\Services\NutritionGenerator;
use Modules\AetherEngine\Services\PeriodizationCalculator;
use Modules\AetherEngine\Services\ProgramEnrichmentService;
use Modules\AetherEngine\Services\ScheduleOptimizer;
use Modules\AetherEngine\Services\WorkoutGenerator;
use Modules\AetherEngine\Support\ExerciseSetPrescriptionBuilder;
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

        $this->registerExerciseIntegrations();

        $this->app->singleton(InjuryTagResolver::class);
        $this->app->singleton(PeriodizationCalculator::class);
        $this->app->singleton(ExerciseSubstitutionService::class);
        $this->app->singleton(ExerciseSetPrescriptionBuilder::class);
        $this->app->singleton(AetherWorkoutSessionService::class);
        $this->app->singleton(AetherProgramEditEventService::class);
        $this->app->singleton(AetherAiGenerationRecorder::class);
        $this->app->singleton(AetherWorkoutLogService::class);
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

    private function registerExerciseIntegrations(): void
    {
        $this->app->singleton(ExerciseGymGifsDbHttpClient::class);
        $this->app->singleton(ExerciseGymGifsDbResponseMapper::class);
        $this->app->singleton(ExerciseGymGifsDbExerciseIndex::class);
        $this->app->singleton(ExerciseGymGifsDbApi::class);
        $this->app->singleton(ExerciseGymGifsDbExerciseMediaProvider::class);
        $this->app->singleton(ExerciseGymGifsDbExerciseCatalogProvider::class);

        $this->app->singleton(MuscleWikiHttpClient::class);
        $this->app->singleton(MuscleWikiResponseMapper::class);
        $this->app->singleton(MuscleWikiExerciseMediaProvider::class);
        $this->app->singleton(MuscleWikiExerciseCatalogProvider::class);

        $this->app->singleton(WorkoutXHttpClient::class);
        $this->app->singleton(WorkoutXExerciseMediaProvider::class);

        $this->app->tag([
            ExerciseGymGifsDbExerciseMediaProvider::class,
            MuscleWikiExerciseMediaProvider::class,
            WorkoutXExerciseMediaProvider::class,
        ], 'aether.exercise-media-providers');

        $this->app->singleton(ExerciseMediaProviderRegistry::class, function ($app): ExerciseMediaProviderRegistry {
            return new ExerciseMediaProviderRegistry($app->tagged('aether.exercise-media-providers'));
        });

        $this->app->singleton(ExerciseMediaResolver::class);

        $this->app->singleton(ExerciseCatalogProviderRegistry::class, function ($app): ExerciseCatalogProviderRegistry {
            return new ExerciseCatalogProviderRegistry([
                'exercise_gym_gifs_db' => $app->make(ExerciseGymGifsDbExerciseCatalogProvider::class),
                'musclewiki' => $app->make(MuscleWikiExerciseCatalogProvider::class),
            ]);
        });

        $this->app->singleton(ExerciseCatalogService::class);
    }
}
