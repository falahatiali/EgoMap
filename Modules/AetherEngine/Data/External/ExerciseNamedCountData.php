<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseNamedCountData
{
    public function __construct(
        public string $name,
        public string $displayName,
        public int $count,
    ) {}
}
