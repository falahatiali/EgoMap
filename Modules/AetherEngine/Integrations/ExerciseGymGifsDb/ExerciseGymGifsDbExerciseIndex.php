<?php

namespace Modules\AetherEngine\Integrations\ExerciseGymGifsDb;

use Illuminate\Support\Facades\Cache;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;

class ExerciseGymGifsDbExerciseIndex
{
    public function __construct(
        private ExerciseGymGifsDbHttpClient $client,
        private ExerciseGymGifsDbResponseMapper $mapper,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if (! $this->client->isConfigured()) {
            return [];
        }

        $cacheKey = 'aether.exercise_gym_gifs_db.exercises.'.$this->client->language();

        /** @var list<array<string, mixed>> $exercises */
        $exercises = Cache::remember(
            $cacheKey,
            (int) config('aether.exercise_api.exercise_gym_gifs_db.cache_ttl', 86400),
            function (): array {
                $payload = $this->client->getExercises();

                return $this->mapper->extractExercises(is_array($payload) ? $payload : []);
            },
        );

        return $exercises;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchItems(): array
    {
        if (! $this->client->isConfigured()) {
            return [];
        }

        $cacheKey = 'aether.exercise_gym_gifs_db.search-items.'.$this->client->language();

        /** @var list<array<string, mixed>> $items */
        $items = Cache::remember(
            $cacheKey,
            (int) config('aether.exercise_api.exercise_gym_gifs_db.cache_ttl', 86400),
            function (): array {
                $payload = $this->client->getSearchIndex();

                return $this->mapper->extractExercises(is_array($payload) ? $payload : []);
            },
        );

        return $items;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function searchFilters(): array
    {
        if (! $this->client->isConfigured()) {
            return [];
        }

        $cacheKey = 'aether.exercise_gym_gifs_db.search-filters.'.$this->client->language();

        /** @var array<string, array<string, string>> $filters */
        $filters = Cache::remember(
            $cacheKey,
            (int) config('aether.exercise_api.exercise_gym_gifs_db.cache_ttl', 86400),
            function (): array {
                $payload = $this->client->getSearchIndex();
                $filters = $payload['filters'] ?? [];

                if (! is_array($filters)) {
                    return [];
                }

                $normalized = [];

                foreach ($filters as $keyword => $mapping) {
                    if (! is_string($keyword) || ! is_array($mapping)) {
                        continue;
                    }

                    $normalized[$keyword] = collect($mapping)
                        ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                        ->all();
                }

                return $normalized;
            },
        );

        return $filters;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByExternalId(string $externalId): ?array
    {
        $externalId = trim($externalId);

        if ($externalId === '') {
            return null;
        }

        foreach ($this->all() as $exercise) {
            if (($exercise['id'] ?? null) === $externalId) {
                return $exercise;
            }
        }

        if (! str_contains($externalId, '/')) {
            return null;
        }

        [$muscle, $slug] = explode('/', $externalId, 2);

        if ($muscle === '' || $slug === '') {
            return null;
        }

        $detail = $this->client->getExercise($muscle, $slug);

        return is_array($detail) ? $detail : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByStableId(int $exerciseId): ?array
    {
        foreach ($this->all() as $exercise) {
            $id = (string) ($exercise['id'] ?? '');

            if ($id !== '' && $this->mapper->stableIntId($id) === $exerciseId) {
                return $exercise;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBestMatchByName(string $name): ?array
    {
        $results = $this->search(new ExerciseSearchQuery(term: $name, limit: 20));

        if ($results !== []) {
            $exact = collect($results)->first(
                fn (array $exercise): bool => $this->normalizeName((string) ($exercise['name'] ?? '')) === $this->normalizeName($name),
            );

            if (is_array($exact)) {
                return $exact;
            }

            return $results[0];
        }

        return $this->findBestMatchInAll($name);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findBestMatchInAll(string $name): ?array
    {
        $needle = $this->normalizeName($name);

        if ($needle === '') {
            return null;
        }

        $exercises = $this->all();
        $slugNeedle = str($needle)->replace(' ', '-')->toString();

        foreach ($exercises as $exercise) {
            if ($this->normalizeName((string) ($exercise['name'] ?? '')) === $needle) {
                return $exercise;
            }
        }

        foreach ($exercises as $exercise) {
            if (($exercise['slug'] ?? null) === $slugNeedle) {
                return $exercise;
            }
        }

        $bestMatch = null;
        $bestScore = 0.0;

        foreach ($exercises as $exercise) {
            $candidate = $this->normalizeName((string) ($exercise['name'] ?? ''));

            if ($candidate === '') {
                continue;
            }

            similar_text($needle, $candidate, $score);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $exercise;
            }
        }

        return $bestScore >= 80.0 ? $bestMatch : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(ExerciseSearchQuery $query): array
    {
        $needle = $this->normalizeName($query->term);
        $tokens = $needle === '' ? [] : (preg_split('/\s+/', $needle) ?: []);

        return collect($this->searchItems())
            ->filter(function (array $exercise) use ($query, $tokens, $needle): bool {
                if ($query->category !== null && ($exercise['category'] ?? null) !== $query->category) {
                    return false;
                }

                if ($tokens === []) {
                    return true;
                }

                return $this->matchesSearchTokens($exercise, $tokens, $needle);
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filterForList(ExerciseListQuery $query): array
    {
        return $this->filter($this->all(), [
            'search' => $query->search,
            'category' => $query->category,
            'muscles' => $query->muscles,
        ]);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function matchesSearchTokens(array $exercise, array $tokens, string $needle): bool
    {
        $name = $this->normalizeName((string) ($exercise['name'] ?? ''));
        $terms = collect($exercise['terms'] ?? [])
            ->filter(fn (mixed $term): bool => is_string($term) && $term !== '')
            ->map(fn (string $term): string => strtolower($term))
            ->values()
            ->all();

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            $tokenMatched = str_contains($name, $token)
                || in_array($token, $terms, true)
                || collect($terms)->contains(fn (string $term): bool => str_contains($term, $token));

            if (! $tokenMatched) {
                $filters = $this->searchFilters()[$token] ?? null;

                if (is_array($filters) && $this->matchesSearchFilters($exercise, $filters)) {
                    continue;
                }

                return false;
            }
        }

        if ($needle !== '' && $name === $needle) {
            return true;
        }

        return $tokens !== [];
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function matchesSearchFilters(array $exercise, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (($exercise[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $exercises
     * @param  array{search?: ?string, category?: ?string, muscles?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    private function filter(array $exercises, array $filters): array
    {
        return collect($exercises)
            ->filter(function (array $exercise) use ($filters): bool {
                if (($filters['search'] ?? null) !== null && ($filters['search'] ?? '') !== '') {
                    $needle = $this->normalizeName((string) $filters['search']);
                    $haystack = $this->normalizeName((string) ($exercise['name'] ?? ''));

                    if ($needle !== '' && ! str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                if (($filters['category'] ?? null) !== null && ($filters['category'] ?? '') !== '') {
                    if (($exercise['category'] ?? null) !== $filters['category']) {
                        return false;
                    }
                }

                if (($filters['muscles'] ?? null) !== null && ($filters['muscles'] ?? '') !== '') {
                    if (($exercise['muscle'] ?? null) !== $filters['muscles']) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }

    private function normalizeName(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}
