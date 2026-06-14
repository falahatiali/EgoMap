<?php

namespace Modules\AetherEngine\Integrations\ExerciseGymGifsDb;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExerciseGymGifsDbHttpClient
{
    public function isConfigured(): bool
    {
        return (bool) config('aether.exercise_api.exercise_gym_gifs_db.enabled', true)
            && $this->baseUrl() !== '';
    }

    public function language(): string
    {
        return (string) config('aether.exercise_api.exercise_gym_gifs_db.language', 'en');
    }

    public function baseUrl(): string
    {
        return rtrim((string) config(
            'aether.exercise_api.exercise_gym_gifs_db.base_url',
            'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0',
        ), '/');
    }

    /**
     * GET /api/index.json
     *
     * @return array<string, mixed>|null
     */
    public function getGlobalIndex(): ?array
    {
        $payload = $this->getAbsoluteJson($this->baseUrl().'/api/index.json');

        return is_array($payload) ? $payload : null;
    }

    /**
     * GET /api/{lang}/index.json
     *
     * @return array<string, mixed>|null
     */
    public function getLanguageIndex(): ?array
    {
        $payload = $this->getJson('index.json');

        return is_array($payload) ? $payload : null;
    }

    /**
     * GET /api/{lang}/exercises.json
     *
     * @return array{count?: int, exercises?: list<array<string, mixed>>}|null
     */
    public function getExercises(): ?array
    {
        $payload = $this->getJson('exercises.json');

        return is_array($payload) ? $payload : null;
    }

    /**
     * GET /api/{lang}/exercises/{muscle}/{slug}.json
     *
     * @return array<string, mixed>|null
     */
    public function getExercise(string $muscle, string $slug): ?array
    {
        $payload = $this->getJson('exercises/'.$muscle.'/'.$slug.'.json');

        return is_array($payload) ? $payload : null;
    }

    /**
     * GET /api/{lang}/search.json
     *
     * @return array<string, mixed>|null
     */
    public function getSearchIndex(): ?array
    {
        $payload = $this->getJson('search.json');

        return is_array($payload) ? $payload : null;
    }

    /**
     * GET /api/{lang}/muscles.json
     *
     * @return list<array<string, mixed>>|null
     */
    public function getMuscles(): ?array
    {
        return $this->getList('muscles.json');
    }

    /**
     * GET /api/{lang}/muscles/{muscle}.json
     *
     * @return array<string, mixed>|null
     */
    public function getMuscleExercises(string $muscle): ?array
    {
        return $this->getCollection('muscles/'.$muscle.'.json');
    }

    /**
     * GET /api/{lang}/equipment.json
     *
     * @return list<array<string, mixed>>|null
     */
    public function getEquipment(): ?array
    {
        return $this->getList('equipment.json');
    }

    /**
     * GET /api/{lang}/equipment/{equipment}.json
     *
     * @return array<string, mixed>|null
     */
    public function getEquipmentExercises(string $equipment): ?array
    {
        return $this->getCollection('equipment/'.$equipment.'.json');
    }

    /**
     * GET /api/{lang}/bodyparts.json
     *
     * @return list<array<string, mixed>>|null
     */
    public function getBodyParts(): ?array
    {
        return $this->getList('bodyparts.json');
    }

    /**
     * GET /api/{lang}/bodyparts/{bodyPart}.json
     *
     * @return array<string, mixed>|null
     */
    public function getBodyPartExercises(string $bodyPart): ?array
    {
        return $this->getCollection('bodyparts/'.$bodyPart.'.json');
    }

    /**
     * GET /api/{lang}/categories.json
     *
     * @return list<array<string, mixed>>|null
     */
    public function getCategories(): ?array
    {
        return $this->getList('categories.json');
    }

    /**
     * GET /api/{lang}/categories/{category}.json
     *
     * @return array<string, mixed>|null
     */
    public function getCategoryExercises(string $category): ?array
    {
        return $this->getCollection('categories/'.$category.'.json');
    }

    /**
     * GET /{muscle}/{slug}.gif
     */
    public function gifUrl(string $muscle, string $slug): string
    {
        return $this->baseUrl().'/'.trim($muscle, '/').'/'.trim($slug, '/').'.gif';
    }

    public function resolveGifUrl(array $exercise): ?string
    {
        $gifUrl = $exercise['gifUrl'] ?? null;

        if (is_string($gifUrl) && $gifUrl !== '') {
            return $gifUrl;
        }

        $file = $exercise['file'] ?? null;

        if (is_string($file) && $file !== '') {
            return $this->baseUrl().'/'.ltrim($file, '/');
        }

        $id = $exercise['id'] ?? null;

        if (is_string($id) && str_contains($id, '/')) {
            [$muscle, $slug] = explode('/', $id, 2);

            if ($muscle !== '' && $slug !== '') {
                return $this->gifUrl($muscle, $slug);
            }
        }

        $muscle = $exercise['muscle'] ?? null;
        $slug = $exercise['slug'] ?? null;

        if (is_string($muscle) && is_string($slug) && $muscle !== '' && $slug !== '') {
            return $this->gifUrl($muscle, $slug);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function getJson(string $path): mixed
    {
        return $this->getAbsoluteJson($this->apiUrl($path));
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function getAbsoluteJson(string $url): mixed
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('aether.exercise_api.exercise_gym_gifs_db.timeout', 15))
                ->connectTimeout((int) config('aether.exercise_api.exercise_gym_gifs_db.connect_timeout', 5))
                ->get($url);

            if (! $response->successful()) {
                Log::warning('ExerciseGymGifsDB request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('ExerciseGymGifsDB request failed', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function getList(string $path): ?array
    {
        $payload = $this->getJson($path);

        return is_array($payload) && array_is_list($payload) ? $payload : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCollection(string $path): ?array
    {
        $payload = $this->getJson($path);

        return is_array($payload) && ! array_is_list($payload) ? $payload : null;
    }

    private function apiUrl(string $path): string
    {
        return $this->baseUrl().'/api/'.$this->language().'/'.ltrim($path, '/');
    }
}
