<?php

namespace Modules\AetherEngine\Integrations\WorkoutX;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkoutXHttpClient
{
    /**
     * @return array{gif_url: ?string, video_url: ?string, image_url: ?string, api_source: string, api_external_id: ?string}|null
     */
    public function fetchByName(string $name): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => (string) config('aether.exercise_api.workoutx.key'),
            ])
                ->timeout((int) config('aether.exercise_api.workoutx.timeout', 8))
                ->connectTimeout((int) config('aether.exercise_api.workoutx.connect_timeout', 3))
                ->get(rtrim((string) config('aether.exercise_api.workoutx.base_url'), '/').'/exercises', [
                    'search' => $name,
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $item = $response->json('data.0') ?? $response->json('exercises.0');

            if (! is_array($item)) {
                return null;
            }

            return [
                'gif_url' => $item['gifUrl'] ?? $item['gif_url'] ?? null,
                'video_url' => $item['videoUrl'] ?? $item['video_url'] ?? null,
                'image_url' => $item['imageUrl'] ?? $item['image_url'] ?? null,
                'api_source' => 'workoutx',
                'api_external_id' => isset($item['id']) ? (string) $item['id'] : null,
            ];
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('WorkoutX API fetch failed', [
                'exercise' => $name,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function isConfigured(): bool
    {
        if (! (bool) config('aether.exercise_api.workoutx.enabled', false)) {
            return false;
        }

        $apiKey = config('aether.exercise_api.workoutx.key');

        return is_string($apiKey) && $apiKey !== '';
    }
}
