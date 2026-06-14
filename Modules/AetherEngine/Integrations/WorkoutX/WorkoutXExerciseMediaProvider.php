<?php

namespace Modules\AetherEngine\Integrations\WorkoutX;

use Modules\AetherEngine\Contracts\ExerciseMediaProviderInterface;
use Modules\AetherEngine\Data\External\ExerciseMediaData;

class WorkoutXExerciseMediaProvider implements ExerciseMediaProviderInterface
{
    public function __construct(private WorkoutXHttpClient $client) {}

    public function source(): string
    {
        return 'workoutx';
    }

    public function priority(): int
    {
        return (int) config('aether.exercise_api.workoutx.priority', 2);
    }

    public function isEnabled(): bool
    {
        return $this->client->isConfigured();
    }

    public function findMediaByName(string $name): ?ExerciseMediaData
    {
        $payload = $this->client->fetchByName($name);

        if ($payload === null) {
            return null;
        }

        return new ExerciseMediaData(
            source: $this->source(),
            externalId: $payload['api_external_id'] ?? null,
            gifUrl: $payload['gif_url'] ?? null,
            videoUrl: $payload['video_url'] ?? null,
            imageUrl: $payload['image_url'] ?? null,
        );
    }

    public function findMediaByExternalId(string $externalId): ?ExerciseMediaData
    {
        return null;
    }
}
