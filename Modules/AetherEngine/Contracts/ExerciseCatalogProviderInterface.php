<?php

namespace Modules\AetherEngine\Contracts;

use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseFilterOptionsData;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseRandomQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

interface ExerciseCatalogProviderInterface
{
    public function source(): string;

    public function isEnabled(): bool;

    public function search(ExerciseSearchQuery $query): PaginatedExerciseSummariesData;

    public function list(ExerciseListQuery $query): PaginatedExerciseSummariesData;

    public function find(int $exerciseId, bool $detailed = false, ?string $gender = null): ?ExerciseDetailData;

    public function random(ExerciseRandomQuery $query): ?ExerciseDetailData;

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function categories(): array;

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function muscles(): array;

    public function filters(): ExerciseFilterOptionsData;
}
