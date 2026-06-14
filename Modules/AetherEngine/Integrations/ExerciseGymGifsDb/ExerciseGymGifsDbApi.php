<?php

namespace Modules\AetherEngine\Integrations\ExerciseGymGifsDb;

use Illuminate\Support\Facades\Cache;
use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbExerciseCollectionData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbGlobalIndexData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbLanguageIndexData;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

class ExerciseGymGifsDbApi
{
    public function __construct(
        private ExerciseGymGifsDbHttpClient $client,
        private ExerciseGymGifsDbResponseMapper $mapper,
        private ExerciseGymGifsDbExerciseIndex $index,
    ) {}

    public function globalIndex(): ?ExerciseGymGifsDbGlobalIndexData
    {
        $payload = $this->remember('global-index', fn (): ?array => $this->client->getGlobalIndex());

        return is_array($payload) ? $this->mapper->mapGlobalIndex($payload) : null;
    }

    public function languageIndex(?string $language = null): ?ExerciseGymGifsDbLanguageIndexData
    {
        if ($language !== null && $language !== $this->client->language()) {
            return null;
        }

        $payload = $this->remember('language-index', fn (): ?array => $this->client->getLanguageIndex());

        return is_array($payload) ? $this->mapper->mapLanguageIndex($payload) : null;
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function muscles(): array
    {
        $payload = $this->remember('muscles', fn (): ?array => $this->client->getMuscles()) ?? [];

        return $this->mapper->mapNamedCounts(is_array($payload) ? $payload : [], 'muscle');
    }

    public function muscleExercises(string $muscle): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->collection('muscle', $muscle, fn (): ?array => $this->client->getMuscleExercises($muscle));
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function equipment(): array
    {
        $payload = $this->remember('equipment', fn (): ?array => $this->client->getEquipment()) ?? [];

        return $this->mapper->mapNamedCounts(is_array($payload) ? $payload : [], 'equipment');
    }

    public function equipmentExercises(string $equipment): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->collection('equipment', $equipment, fn (): ?array => $this->client->getEquipmentExercises($equipment));
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function bodyParts(): array
    {
        $payload = $this->remember('bodyparts', fn (): ?array => $this->client->getBodyParts()) ?? [];

        return $this->mapper->mapNamedCounts(is_array($payload) ? $payload : [], 'bodyPart');
    }

    public function bodyPartExercises(string $bodyPart): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->collection('bodyPart', $bodyPart, fn (): ?array => $this->client->getBodyPartExercises($bodyPart));
    }

    /**
     * @return list<ExerciseNamedCountData>
     */
    public function categories(): array
    {
        $payload = $this->remember('categories', fn (): ?array => $this->client->getCategories()) ?? [];

        return $this->mapper->mapNamedCounts(is_array($payload) ? $payload : [], 'category');
    }

    public function categoryExercises(string $category): ?ExerciseGymGifsDbExerciseCollectionData
    {
        return $this->collection('category', $category, fn (): ?array => $this->client->getCategoryExercises($category));
    }

    public function allExercises(int $limit = 20, int $offset = 0): PaginatedExerciseSummariesData
    {
        $exercises = $this->index->all();

        return $this->paginate($exercises, $limit, $offset);
    }

    public function search(ExerciseSearchQuery $query): PaginatedExerciseSummariesData
    {
        $results = $this->index->search($query);

        return $this->paginate($results, $query->limit, $query->offset);
    }

    public function list(ExerciseListQuery $query): PaginatedExerciseSummariesData
    {
        $results = $this->index->filterForList($query);

        return $this->paginate($results, $query->limit, $query->offset);
    }

    public function exerciseDetail(string $muscle, string $slug): ?ExerciseDetailData
    {
        $payload = $this->client->getExercise($muscle, $slug);

        return is_array($payload) ? $this->mapper->mapDetail($payload, $this->client) : null;
    }

    public function exerciseDetailByExternalId(string $externalId): ?ExerciseDetailData
    {
        $payload = $this->index->findByExternalId($externalId);

        return is_array($payload) ? $this->mapper->mapDetail($payload, $this->client) : null;
    }

    public function gifUrl(string $muscle, string $slug): string
    {
        return $this->client->gifUrl($muscle, $slug);
    }

    /**
     * @param  callable(): (?array)  $fetch
     */
    private function collection(string $type, string $key, callable $fetch): ?ExerciseGymGifsDbExerciseCollectionData
    {
        $payload = $this->remember($type.'.'.$key, $fetch);

        if (! is_array($payload)) {
            return null;
        }

        return $this->mapper->mapExerciseCollection($payload, match ($type) {
            'muscle' => 'muscle',
            'equipment' => 'equipment',
            'bodyPart' => 'bodyPart',
            'category' => 'category',
            default => $type,
        });
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     */
    private function paginate(array $exercises, int $limit, int $offset): PaginatedExerciseSummariesData
    {
        return $this->mapper->mapPaginatedSummaries(
            array_slice($exercises, $offset, $limit),
            count($exercises),
            $limit,
            $offset,
        );
    }

    /**
     * @param  callable(): mixed  $resolver
     */
    private function remember(string $suffix, callable $resolver): mixed
    {
        return Cache::remember(
            'aether.exercise_gym_gifs_db.'.$this->client->language().'.'.$suffix,
            (int) config('aether.exercise_api.exercise_gym_gifs_db.cache_ttl', 86400),
            $resolver,
        );
    }
}
