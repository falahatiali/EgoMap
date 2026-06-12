<?php

namespace Modules\AetherEngine\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkoutXApiClient
{
    /**
     * @return array{gif_url: ?string, video_url: ?string, image_url: ?string, api_source: string, api_external_id: ?string}|null
     */
    public function fetchByName(string $name): ?array
    {
        if (! config('aether.exercise_api.workoutx.enabled', false)) {
            return null;
        }

        $apiKey = config('aether.exercise_api.workoutx.key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
            ])
                ->timeout(8)
                ->get(config('aether.exercise_api.workoutx.base_url').'/exercises', [
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
        } catch (\Throwable $exception) {
            Log::warning('WorkoutX API fetch failed', [
                'exercise' => $name,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
