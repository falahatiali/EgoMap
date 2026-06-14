<?php

namespace Modules\AetherEngine\Services\ExerciseMedia;

use Modules\AetherEngine\Contracts\ExerciseMediaProviderInterface;
use Modules\AetherEngine\Data\External\ExerciseMediaData;

class ExerciseMediaProviderRegistry
{
    /**
     * @var list<ExerciseMediaProviderInterface>
     */
    private array $providers;

    /**
     * @param  iterable<ExerciseMediaProviderInterface>  $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = collect($providers)
            ->filter(fn (ExerciseMediaProviderInterface $provider): bool => $provider->isEnabled())
            ->sortBy(fn (ExerciseMediaProviderInterface $provider): int => $provider->priority())
            ->values()
            ->all();
    }

    /**
     * @return list<ExerciseMediaProviderInterface>
     */
    public function enabled(): array
    {
        return $this->providers;
    }

    public function resolveByName(string $name): ?ExerciseMediaData
    {
        foreach ($this->providers as $provider) {
            $media = $provider->findMediaByName($name);

            if ($media instanceof ExerciseMediaData) {
                return $media;
            }
        }

        return null;
    }

    public function resolveByExternalId(string $source, string $externalId): ?ExerciseMediaData
    {
        foreach ($this->providers as $provider) {
            if ($provider->source() !== $source) {
                continue;
            }

            $media = $provider->findMediaByExternalId($externalId);

            if ($media instanceof ExerciseMediaData) {
                return $media;
            }
        }

        return null;
    }
}
