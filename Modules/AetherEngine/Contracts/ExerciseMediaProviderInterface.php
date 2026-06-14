<?php

namespace Modules\AetherEngine\Contracts;

use Modules\AetherEngine\Data\External\ExerciseMediaData;

interface ExerciseMediaProviderInterface
{
    public function source(): string;

    public function priority(): int;

    public function isEnabled(): bool;

    public function findMediaByName(string $name): ?ExerciseMediaData;

    public function findMediaByExternalId(string $externalId): ?ExerciseMediaData;
}
