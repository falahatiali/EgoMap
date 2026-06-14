<?php

namespace Modules\AetherEngine\Services\ExerciseCatalog;

use InvalidArgumentException;
use Modules\AetherEngine\Contracts\ExerciseCatalogProviderInterface;

class ExerciseCatalogProviderRegistry
{
    /**
     * @param  array<string, ExerciseCatalogProviderInterface>  $providers
     */
    public function __construct(private array $providers) {}

    public function get(?string $source = null): ExerciseCatalogProviderInterface
    {
        $source ??= (string) config('aether.exercise_catalog.default', 'musclewiki');

        $provider = $this->providers[$source] ?? null;

        if (! $provider instanceof ExerciseCatalogProviderInterface) {
            throw new InvalidArgumentException("Unsupported exercise catalog provider [{$source}].");
        }

        if (! $provider->isEnabled()) {
            throw new InvalidArgumentException("Exercise catalog provider [{$source}] is disabled.");
        }

        return $provider;
    }

    /**
     * @return list<string>
     */
    public function availableSources(): array
    {
        return collect($this->providers)
            ->filter(fn (ExerciseCatalogProviderInterface $provider): bool => $provider->isEnabled())
            ->keys()
            ->values()
            ->all();
    }
}
