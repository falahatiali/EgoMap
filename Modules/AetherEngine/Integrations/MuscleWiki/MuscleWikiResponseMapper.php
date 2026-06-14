<?php

namespace Modules\AetherEngine\Integrations\MuscleWiki;

use Modules\AetherEngine\Data\External\ExerciseDetailData;
use Modules\AetherEngine\Data\External\ExerciseFilterOptionsData;
use Modules\AetherEngine\Data\External\ExerciseMediaData;
use Modules\AetherEngine\Data\External\ExerciseNamedCountData;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Data\External\ExerciseSummaryData;
use Modules\AetherEngine\Data\External\PaginatedExerciseSummariesData;

class MuscleWikiResponseMapper
{
    public const SOURCE = 'musclewiki';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapSummary(array $payload): ExerciseSummaryData
    {
        return new ExerciseSummaryData(
            source: self::SOURCE,
            id: (int) ($payload['id'] ?? 0),
            name: (string) ($payload['name'] ?? ''),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapDetail(array $payload): ExerciseDetailData
    {
        $videos = $this->normalizeVideos($payload['videos'] ?? []);
        $gender = (string) config('aether.exercise_api.musclewiki.default_gender', 'male');

        return new ExerciseDetailData(
            source: self::SOURCE,
            id: (int) ($payload['id'] ?? 0),
            name: (string) ($payload['name'] ?? ''),
            primaryMuscles: $this->normalizeStringList($payload['primary_muscles'] ?? []),
            category: isset($payload['category']) ? (string) $payload['category'] : null,
            force: isset($payload['force']) ? (string) $payload['force'] : null,
            grips: $this->normalizeStringList($payload['grips'] ?? []),
            mechanic: isset($payload['mechanic']) ? (string) $payload['mechanic'] : null,
            difficulty: isset($payload['difficulty']) ? (string) $payload['difficulty'] : null,
            steps: $this->normalizeStringList($payload['steps'] ?? []),
            videos: $videos,
            bodymapMaleUrl: isset($payload['bodymap_male']) ? (string) $payload['bodymap_male'] : null,
            bodymapFemaleUrl: isset($payload['bodymap_female']) ? (string) $payload['bodymap_female'] : null,
            media: $this->mapMedia($payload, $videos, $gender),
            raw: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapPaginatedSummaries(array $payload): PaginatedExerciseSummariesData
    {
        $results = collect($payload['results'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): ExerciseSummaryData => $this->mapSummary($item))
            ->values()
            ->all();

        return new PaginatedExerciseSummariesData(
            source: self::SOURCE,
            total: (int) ($payload['total'] ?? count($results)),
            limit: (int) ($payload['limit'] ?? count($results)),
            offset: (int) ($payload['offset'] ?? 0),
            count: (int) ($payload['count'] ?? count($results)),
            results: $results,
        );
    }

    /**
     * MuscleWiki `/search` returns a bare exercise array (not paginated metadata).
     *
     * @param  array<int, array<string, mixed>>|array<string, mixed>  $payload
     */
    public function mapSearchResults(array $payload, ExerciseSearchQuery $query): PaginatedExerciseSummariesData
    {
        if ($this->isListOfExercises($payload)) {
            $results = collect($payload)
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): ExerciseSummaryData => $this->mapSummary($item))
                ->values()
                ->all();

            return new PaginatedExerciseSummariesData(
                source: self::SOURCE,
                total: count($results),
                limit: $query->limit,
                offset: $query->offset,
                count: count($results),
                results: $results,
            );
        }

        return $this->mapPaginatedSummaries($payload);
    }

    /**
     * @param  array<int, array<string, mixed>>|array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    public function firstSearchExercise(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        if ($this->isListOfExercises($payload)) {
            $first = $payload[0] ?? null;

            return is_array($first) ? $first : null;
        }

        $first = $payload['results'][0] ?? null;

        return is_array($first) ? $first : null;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<ExerciseNamedCountData>
     */
    public function mapNamedCounts(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item): ExerciseNamedCountData {
                $name = (string) ($item['name'] ?? '');

                return new ExerciseNamedCountData(
                    name: $name,
                    displayName: (string) ($item['display_name'] ?? $name),
                    count: (int) ($item['count'] ?? 0),
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function mapFilters(array $payload): ExerciseFilterOptionsData
    {
        $options = [];

        foreach ($payload as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_array($value)) {
                $options[$key] = $this->normalizeStringList($value);
            }
        }

        return new ExerciseFilterOptionsData(
            source: self::SOURCE,
            options: $options,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $videos
     */
    public function mapMedia(array $payload, array $videos, ?string $preferredGender = null): ExerciseMediaData
    {
        $preferredGender ??= (string) config('aether.exercise_api.musclewiki.default_gender', 'male');
        $primaryVideo = $this->pickPrimaryVideo($videos, $preferredGender);
        $primaryImage = $this->pickPrimaryImage($videos, $preferredGender);

        return new ExerciseMediaData(
            source: self::SOURCE,
            externalId: isset($payload['id']) ? (string) $payload['id'] : null,
            gifUrl: null,
            videoUrl: $primaryVideo,
            imageUrl: $primaryImage,
            videos: $videos,
            metadata: [
                'name' => $payload['name'] ?? null,
                'video_count' => $payload['video_count'] ?? null,
                'image_count' => $payload['image_count'] ?? null,
                'step_count' => $payload['step_count'] ?? null,
            ],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizeVideos(mixed $videos): array
    {
        if (! is_array($videos)) {
            return [];
        }

        return collect($videos)
            ->filter(fn (mixed $video): bool => is_array($video))
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     */
    private function pickPrimaryVideo(array $videos, string $preferredGender): ?string
    {
        $frontMatch = $this->findVideo($videos, $preferredGender, 'front');

        if ($frontMatch !== null) {
            return $this->extractStreamUrl($frontMatch);
        }

        $genderMatch = $this->findVideo($videos, $preferredGender);

        if ($genderMatch !== null) {
            return $this->extractStreamUrl($genderMatch);
        }

        foreach ($videos as $video) {
            $url = $this->extractStreamUrl($video);

            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     */
    private function pickPrimaryImage(array $videos, string $preferredGender): ?string
    {
        $frontMatch = $this->findVideo($videos, $preferredGender, 'front');

        if ($frontMatch !== null) {
            $image = $frontMatch['og_image'] ?? $frontMatch['og_image_url'] ?? $frontMatch['image_url'] ?? null;

            if (is_string($image) && $image !== '') {
                return $this->absoluteUrl($image);
            }
        }

        foreach ($videos as $video) {
            if (($video['gender'] ?? null) !== $preferredGender) {
                continue;
            }

            $image = $video['og_image'] ?? $video['og_image_url'] ?? $video['image_url'] ?? null;

            if (is_string($image) && $image !== '') {
                return $this->absoluteUrl($image);
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $videos
     * @return array<string, mixed>|null
     */
    private function findVideo(array $videos, string $preferredGender, ?string $angle = null): ?array
    {
        foreach ($videos as $video) {
            if (($video['gender'] ?? null) !== $preferredGender) {
                continue;
            }

            if ($angle !== null && ($video['angle'] ?? null) !== $angle) {
                continue;
            }

            return $video;
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>|array<string, mixed>  $payload
     */
    private function isListOfExercises(array $payload): bool
    {
        if ($payload === []) {
            return true;
        }

        if (! array_is_list($payload)) {
            return false;
        }

        return is_array($payload[0] ?? null) && array_key_exists('id', $payload[0]);
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private function extractStreamUrl(array $video): ?string
    {
        foreach (['url', 'video_url', 'stream_url', 'branded_url', 'unbranded_url', 'file'] as $key) {
            $value = $video[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $this->absoluteUrl($value);
            }
        }

        $filename = $video['filename'] ?? null;

        if (is_string($filename) && $filename !== '') {
            return $this->absoluteUrl('/stream/videos/branded/'.$filename);
        }

        return null;
    }

    private function absoluteUrl(string $value): string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return rtrim((string) config('aether.exercise_api.musclewiki.base_url', 'https://api.musclewiki.com'), '/').'/'.ltrim($value, '/');
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
