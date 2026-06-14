<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseSummaryData
{
    public function __construct(
        public string $source,
        public int $id,
        public string $name,
    ) {}
}
