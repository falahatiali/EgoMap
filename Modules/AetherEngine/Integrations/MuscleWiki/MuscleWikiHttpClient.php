<?php

namespace Modules\AetherEngine\Integrations\MuscleWiki;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MuscleWikiHttpClient
{
    public function isConfigured(): bool
    {
        $key = config('aether.exercise_api.musclewiki.key');

        return is_string($key) && $key !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function health(): ?array
    {
        return $this->get('/health');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function statistics(): ?array
    {
        return $this->get('/statistics');
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function categories(): ?array
    {
        return $this->get('/categories');
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function muscles(): ?array
    {
        return $this->get('/muscles');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function filters(): ?array
    {
        return $this->get('/filters');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function listExercises(array $query = []): ?array
    {
        return $this->get('/exercises', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function getExercise(int $exerciseId, array $query = []): ?array
    {
        return $this->get('/exercises/'.$exerciseId, $query);
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getExerciseVideos(int $exerciseId): ?array
    {
        $payload = $this->get('/exercises/'.$exerciseId.'/videos');

        if (! is_array($payload)) {
            return null;
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        $videos = $payload['videos'] ?? null;

        return is_array($videos) ? $videos : null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>|null
     */
    public function search(array $query): ?array
    {
        $payload = $this->get('/search', $query);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function random(array $query = []): ?array
    {
        return $this->get('/random', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function get(string $path, array $query = []): mixed
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request()
                ->get($this->baseUrl().$path, $query);

            if (! $response->successful()) {
                $this->logFailure($path, $response);

                return null;
            }

            return $response->json();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('MuscleWiki API request failed', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-API-Key' => (string) config('aether.exercise_api.musclewiki.key'),
            'Accept' => 'application/json',
        ])
            ->timeout((int) config('aether.exercise_api.musclewiki.timeout', 80))
            ->connectTimeout((int) config('aether.exercise_api.musclewiki.connect_timeout', 3))
            ->retry(
                times: (int) config('aether.exercise_api.musclewiki.retry_times', 2),
                sleepMilliseconds: (int) config('aether.exercise_api.musclewiki.retry_sleep_ms', 250),
                when: function (\Throwable $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException && $exception->response !== null) {
                        return $exception->response->serverError()
                            || $exception->response->status() === 429;
                    }

                    return false;
                },
                throw: false,
            );
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('aether.exercise_api.musclewiki.base_url', 'https://api.musclewiki.com'), '/');
    }

    private function logFailure(string $path, Response $response): void
    {
        Log::warning('MuscleWiki API returned an error response', [
            'path' => $path,
            'status' => $response->status(),
            'body' => $response->json('detail') ?? $response->body(),
        ]);
    }
}
