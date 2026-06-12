<?php

namespace App\Support\GhostMode;

use App\DataTransferObjects\GhostMode\GhostModeActor;
use App\Services\NoContact\GhostModeActorResolver;
use App\Services\NoContact\GhostModeOrchestrator;
use App\Support\LocaleConfig;

/**
 * Web adapter so Livewire uses the same Ghost Mode application layer as the API.
 */
class GhostModeClient
{
    public function __construct(
        private readonly GhostModeActorResolver $actorResolver,
        private readonly GhostModeOrchestrator $orchestrator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bootstrap(?string $locale = null): array
    {
        return $this->orchestrator->bootstrap(
            $this->actorResolver->fromWeb(),
            LocaleConfig::resolve($locale ?? app()->getLocale()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function startProtocol(int $durationDays, ?string $locale = null): array
    {
        return $this->orchestrator->startProtocol(
            $this->actorResolver->fromWeb(),
            $durationDays,
            LocaleConfig::resolve($locale ?? app()->getLocale()),
        );
    }

    public function actor(): GhostModeActor
    {
        return $this->actorResolver->fromWeb();
    }
}
