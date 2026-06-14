<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseFilterOptionsData
{
    /**
     * @param  array<string, list<string>>  $options
     */
    public function __construct(
        public string $source,
        public array $options,
    ) {}
}
