<?php

namespace Modules\AetherEngine\Services\ExerciseCatalog;

use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseFilterOptionsData;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseRandomQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

class ExerciseCatalogService
{
    public function __construct(private ExerciseCatalogProviderRegistry $providers) {}

    public function search(ExerciseSearchQuery $query, ?string $source = null): PaginatedExerciseSummariesData
    {
        return $this->providers->get($source)->search($query);
    }

    public function list(ExerciseListQuery $query, ?string $source = null): PaginatedExerciseSummariesData
    {
        return $this->providers->get($source)->list($query);
    }

    public function find(int $exerciseId, bool $detailed = false, ?string $gender = null, ?string $source = null): ?ExerciseDetailData
    {
        return $this->providers->get($source)->find($exerciseId, $detailed, $gender);
    }

    public function random(ExerciseRandomQuery $query, ?string $source = null): ?ExerciseDetailData
    {
        return $this->providers->get($source)->random($query);
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function categories(?string $source = null): array
    {
        return $this->providers->get($source)->categories();
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function muscles(?string $source = null): array
    {
        return $this->providers->get($source)->muscles();
    }

    public function filters(?string $source = null): ExerciseFilterOptionsData
    {
        return $this->providers->get($source)->filters();
    }
}
