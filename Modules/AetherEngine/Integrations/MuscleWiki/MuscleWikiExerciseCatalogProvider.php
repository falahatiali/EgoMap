<?php

namespace Modules\AetherEngine\Integrations\MuscleWiki;

use Modules\AetherEngine\Contracts\ExerciseCatalogProviderInterface;
use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseFilterOptionsData;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseRandomQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

class MuscleWikiExerciseCatalogProvider implements ExerciseCatalogProviderInterface
{
    public function __construct(
        private MuscleWikiHttpClient $client,
        private MuscleWikiResponseMapper $mapper,
    ) {}

    public function source(): string
    {
        return MuscleWikiResponseMapper::SOURCE;
    }

    public function isEnabled(): bool
    {
        return (bool) config('aether.exercise_api.musclewiki.enabled', false)
            && $this->client->isConfigured();
    }

    public function search(ExerciseSearchQuery $query): PaginatedExerciseSummariesData
    {
        $payload = $this->client->search($query->toQueryParameters()) ?? [];

        return $this->mapper->mapSearchResults($payload, $query);
    }

    public function list(ExerciseListQuery $query): PaginatedExerciseSummariesData
    {
        $payload = $this->client->listExercises($query->toQueryParameters()) ?? [];

        return $this->mapper->mapPaginatedSummaries($payload);
    }

    public function find(int $exerciseId, bool $detailed = false, ?string $gender = null): ?ExerciseDetailData
    {
        $payload = $this->client->getExercise($exerciseId, array_filter([
            'detail' => $detailed ? 'true' : null,
            'gender' => $gender ?? config('aether.exercise_api.musclewiki.default_gender', 'male'),
        ], fn (mixed $value): bool => $value !== null));

        if (! is_array($payload)) {
            return null;
        }

        return $this->mapper->mapDetail($payload);
    }

    public function random(ExerciseRandomQuery $query): ?ExerciseDetailData
    {
        $payload = $this->client->random($query->toQueryParameters());

        if (! is_array($payload)) {
            return null;
        }

        return $this->mapper->mapDetail($payload);
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function categories(): array
    {
        $payload = $this->client->categories() ?? [];

        return $this->mapper->mapNamedCounts(is_array($payload) ? $payload : []);
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function muscles(): array
    {
        $payload = $this->client->muscles() ?? [];

        return $this->mapper->mapNamedCounts(is_array($payload) ? $payload : []);
    }

    public function filters(): ExerciseFilterOptionsData
    {
        $payload = $this->client->filters() ?? [];

        return $this->mapper->mapFilters(is_array($payload) ? $payload : []);
    }
}
