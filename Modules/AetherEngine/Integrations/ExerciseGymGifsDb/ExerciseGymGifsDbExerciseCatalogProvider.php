<?php

namespace Modules\AetherEngine\Integrations\ExerciseGymGifsDb;

use Modules\AetherEngine\Contracts\ExerciseCatalogProviderInterface;
use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseFilterOptionsData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbExerciseCollectionData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbGlobalIndexData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbLanguageIndexData;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseRandomQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

class ExerciseGymGifsDbExerciseCatalogProvider implements ExerciseCatalogProviderInterface
{
    public function __construct(
        private ExerciseGymGifsDbApi $api,
        private ExerciseGymGifsDbExerciseIndex $index,
        private ExerciseGymGifsDbHttpClient $client,
        private ExerciseGymGifsDbResponseMapper $mapper,
    ) {}

    public function source(): string
    {
        return ExerciseGymGifsDbResponseMapper::SOURCE;
    }

    public function isEnabled(): bool
    {
        return $this->client->isConfigured();
    }

    public function globalIndex(): ?ExerciseGymGifsDbGlobalIndexData
    {
        return $this->api->globalIndex();
    }

    public function languageIndex(): ?ExerciseGymGifsDbLanguageIndexData
    {
        return $this->api->languageIndex();
    }

    public function search(ExerciseSearchQuery $query): PaginatedExerciseSummariesData
    {
        return $this->api->search($query);
    }

    public function list(ExerciseListQuery $query): PaginatedExerciseSummariesData
    {
        return $this->api->list($query);
    }

    public function find(int $exerciseId, bool $detailed = false, ?string $gender = null): ?ExerciseDetailData
    {
        $exercise = $this->index->findByStableId($exerciseId);

        if ($exercise === null) {
            return null;
        }

        return $this->mapper->mapDetail($exercise, $this->client);
    }

    public function findByExternalId(string $externalId): ?ExerciseDetailData
    {
        return $this->api->exerciseDetailByExternalId($externalId);
    }

    public function random(ExerciseRandomQuery $query): ?ExerciseDetailData
    {
        $exercises = collect($this->index->all())
            ->filter(function (array $exercise) use ($query): bool {
                if ($query->category !== null && ($exercise['category'] ?? null) !== $query->category) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();

        if ($exercises === []) {
            return null;
        }

        $exercise = $exercises[array_rand($exercises)];

        return $this->mapper->mapDetail($exercise, $this->client);
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function categories(): array
    {
        return $this->api->categories();
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function muscles(): array
    {
        return $this->api->muscles();
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function equipment(): array
    {
        return $this->api->equipment();
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function bodyParts(): array
    {
        return $this->api->bodyParts();
    }

    public function muscleExercises(string $muscle): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->api->muscleExercises($muscle);
    }

    public function equipmentExercises(string $equipment): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->api->equipmentExercises($equipment);
    }

    public function bodyPartExercises(string $bodyPart): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->api->bodyPartExercises($bodyPart);
    }

    public function categoryExercises(string $category): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->api->categoryExercises($category);
    }

    public function filters(): ExerciseFilterOptionsData
    {
        return $this->mapper->mapFilters([
            'muscles' => collect($this->api->muscles())->pluck('name')->all(),
            'equipment' => collect($this->api->equipment())->pluck('name')->all(),
            'bodyPart' => collect($this->api->bodyParts())->pluck('name')->all(),
            'category' => collect($this->api->categories())->pluck('name')->all(),
        ]);
    }
}
