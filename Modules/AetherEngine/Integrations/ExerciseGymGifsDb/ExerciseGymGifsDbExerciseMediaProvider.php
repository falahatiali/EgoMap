<?php

namespace Modules\AetherEngine\Integrations\ExerciseGymGifsDb;

use Modules\AetherEngine\Contracts\ExerciseMediaProviderInterface;
use Modules\AetherEngine\Data\External\ExerciseMediaData;

class ExerciseGymGifsDbExerciseMediaProvider implements ExerciseMediaProviderInterface
{
    public function __construct(
        private ExerciseGymGifsDbExerciseIndex $index,
        private ExerciseGymGifsDbResponseMapper $mapper,
        private ExerciseGymGifsDbHttpClient $client,
    ) {}

    public function source(): string
    {
        return ExerciseGymGifsDbResponseMapper::SOURCE;
    }

    public function priority(): int
    {
        return (int) config('aether.exercise_api.exercise_gym_gifs_db.priority', 0);
    }

    public function isEnabled(): bool
    {
        return $this->client->isConfigured();
    }

    public function findMediaByName(string $name): ?ExerciseMediaData
    {
        $exercise = $this->index->findBestMatchByName($name);

        if ($exercise === null) {
            return null;
        }

        return $this->mapper->mapMedia($exercise, $this->client);
    }

    public function findMediaByExternalId(string $externalId): ?ExerciseMediaData
    {
        $exercise = $this->index->findByExternalId($externalId);

        if ($exercise === null) {
            return null;
        }

        return $this->mapper->mapMedia($exercise, $this->client);
    }
}
