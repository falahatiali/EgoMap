<?php

namespace Modules\AetherEngine\Integrations\MuscleWiki;

use Modules\AetherEngine\Contracts\ExerciseMediaProviderInterface;
use Modules\AetherEngine\Data\External\ExerciseMediaData;

class MuscleWikiExerciseMediaProvider implements ExerciseMediaProviderInterface
{
    public function __construct(
        private MuscleWikiHttpClient $client,
        private MuscleWikiResponseMapper $mapper,
    ) {}

    public function source(): string
    {
        return MuscleWikiResponseMapper::SOURCE;
    }

    public function priority(): int
    {
        return (int) config('aether.exercise_api.musclewiki.priority', 1);
    }

    public function isEnabled(): bool
    {
        return (bool) config('aether.exercise_api.musclewiki.enabled', false)
            && $this->client->isConfigured();
    }

    public function findMediaByName(string $name): ?ExerciseMediaData
    {
        $search = $this->client->search([
            'q' => $name,
            'limit' => 1,
        ]);

        $first = $this->mapper->firstSearchExercise($search);

        if ($first === null) {
            return null;
        }

        $gender = (string) config('aether.exercise_api.musclewiki.default_gender', 'male');
        $videos = $this->mapper->normalizeVideos($first['videos'] ?? []);

        if ($videos !== []) {
            return $this->mapper->mapMedia($first, $videos, $gender);
        }

        $exerciseId = (int) ($first['id'] ?? 0);

        if ($exerciseId <= 0) {
            return null;
        }

        return $this->findMediaByExternalId((string) $exerciseId);
    }

    public function findMediaByExternalId(string $externalId): ?ExerciseMediaData
    {
        if (! ctype_digit($externalId)) {
            return null;
        }

        $payload = $this->client->getExercise((int) $externalId, [
            'gender' => config('aether.exercise_api.musclewiki.default_gender', 'male'),
        ]);

        if (! is_array($payload)) {
            return null;
        }

        return $this->mapper->mapDetail($payload)->media;
    }
}
