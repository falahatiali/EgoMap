<?php

namespace Modules\AetherEngine\Integrations\ExerciseGymGifsDb;

use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseFilterOptionsData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbExerciseCollectionData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbGlobalIndexData;
use Modules\AetherEngine\Data\External\ExerciseGymGifsDbLanguageIndexData;
use Modules\AetherEngine\Data\External\ExerciseMediaData;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseSummaryData;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

class ExerciseGymGifsDbResponseMapper
{
    public const SOURCE = 'exercise_gym_gifs_db';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapSummary(array $payload): ExerciseSummaryData
    {
        return new ExerciseSummaryData(
            source: self::SOURCE,
            id: $this->stableIntId((string) ($payload['id'] ?? '')),
            name: (string) ($payload['name'] ?? ''),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapDetail(array $payload, ?ExerciseGymGifsDbHttpClient $client = null): ExerciseDetailData
    {
        $client ??= app(ExerciseGymGifsDbHttpClient::class);
        $media = $this->mapMedia($payload, $client);

        return new ExerciseDetailData(
            source: self::SOURCE,
            id: $this->stableIntId((string) ($payload['id'] ?? '')),
            name: (string) ($payload['name'] ?? ''),
            primaryMuscles: $this->normalizeMuscle((string) ($payload['muscle'] ?? '')),
            category: isset($payload['category']) ? (string) $payload['category'] : null,
            steps: $this->normalizeStringList($payload['instructions'] ?? []),
            media: $media,
            raw: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapMedia(array $payload, ?ExerciseGymGifsDbHttpClient $client = null): ExerciseMediaData
    {
        $client ??= app(ExerciseGymGifsDbHttpClient::class);
        $gifUrl = $client->resolveGifUrl($payload);

        return new ExerciseMediaData(
            source: self::SOURCE,
            externalId: isset($payload['id']) ? (string) $payload['id'] : null,
            gifUrl: $gifUrl,
            videoUrl: null,
            imageUrl: $gifUrl,
            metadata: [
                'name' => $payload['name'] ?? null,
                'muscle' => $payload['muscle'] ?? null,
                'equipment' => $payload['equipment'] ?? null,
                'bodyPart' => $payload['bodyPart'] ?? null,
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     */
    public function mapPaginatedSummaries(array $exercises, int $total, int $limit, int $offset): PaginatedExerciseSummariesData
    {
        $results = collect($exercises)
            ->map(fn (array $exercise): ExerciseSummaryData => $this->mapSummary($exercise))
            ->values()
            ->all();

        return new PaginatedExerciseSummariesData(
            source: self::SOURCE,
            total: $total,
            limit: $limit,
            offset: $offset,
            count: count($results),
            results: $results,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  'muscle'|'equipment'|'category'|'bodyPart'  $key
     * @return list<ExerciseNamedCountData>
     */
    public function mapNamedCounts(array $items, string $key): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item) use ($key): ExerciseNamedCountData {
                $name = (string) ($item[$key] ?? '');

                return new ExerciseNamedCountData(
                    name: $name,
                    displayName: str($name)->replace('-', ' ')->title()->toString(),
                    count: (int) ($item['count'] ?? 0),
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, list<string>>  $options
     */
    public function mapFilters(array $options): ExerciseFilterOptionsData
    {
        return new ExerciseFilterOptionsData(
            source: self::SOURCE,
            options: $options,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapGlobalIndex(array $payload): ExerciseGymGifsDbGlobalIndexData
    {
        $languages = collect($payload['languages'] ?? [])
            ->filter(fn (mixed $language): bool => is_string($language) && $language !== '')
            ->values()
            ->all();

        return new ExerciseGymGifsDbGlobalIndexData(
            name: (string) ($payload['name'] ?? ''),
            baseUrl: (string) ($payload['baseUrl'] ?? ''),
            generatedAt: (string) ($payload['generatedAt'] ?? ''),
            languages: $languages,
            defaultLanguage: (string) ($payload['defaultLanguage'] ?? 'en'),
            totals: is_array($payload['totals'] ?? null) ? $payload['totals'] : [],
            endpoints: is_array($payload['endpoints'] ?? null) ? $payload['endpoints'] : [],
            raw: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapLanguageIndex(array $payload): ExerciseGymGifsDbLanguageIndexData
    {
        $muscles = $this->mapNamedCounts(
            is_array($payload['muscles'] ?? null) ? $payload['muscles'] : [],
            'muscle',
        );

        return new ExerciseGymGifsDbLanguageIndexData(
            name: (string) ($payload['name'] ?? ''),
            language: (string) ($payload['language'] ?? ''),
            baseUrl: (string) ($payload['baseUrl'] ?? ''),
            generatedAt: (string) ($payload['generatedAt'] ?? ''),
            totals: is_array($payload['totals'] ?? null) ? $payload['totals'] : [],
            endpoints: is_array($payload['endpoints'] ?? null) ? $payload['endpoints'] : [],
            muscles: $muscles,
            raw: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  'muscle'|'equipment'|'bodyPart'|'category'  $collectionType
     */
    public function mapExerciseCollection(array $payload, string $collectionType): ExerciseGymGifsDbExerciseCollectionData
    {
        $collectionKey = (string) ($payload[$collectionType] ?? '');
        $exercises = collect($this->extractExercises($payload))
            ->map(fn (array $exercise): ExerciseSummaryData => $this->mapSummary($exercise))
            ->values()
            ->all();

        return new ExerciseGymGifsDbExerciseCollectionData(
            source: self::SOURCE,
            collectionType: $collectionType,
            collectionKey: $collectionKey,
            count: (int) ($payload['count'] ?? count($exercises)),
            exercises: $exercises,
            raw: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function extractExercises(array $payload): array
    {
        $exercises = $payload['exercises'] ?? $payload['items'] ?? [];

        if (! is_array($exercises)) {
            return [];
        }

        return array_values(array_filter($exercises, fn (mixed $item): bool => is_array($item)));
    }

    public function stableIntId(string $externalId): int
    {
        return (int) sprintf('%u', crc32($externalId));
    }

    /**
     * @return list<string>
     */
    private function normalizeMuscle(string $muscle): array
    {
        if ($muscle === '') {
            return [];
        }

        return [str($muscle)->replace('-', ' ')->title()->toString()];
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }
}
