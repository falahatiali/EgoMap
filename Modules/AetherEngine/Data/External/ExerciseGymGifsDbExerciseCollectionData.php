<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseGymGifsDbExerciseCollectionData
{
    /**
     * @param  list<ExerciseSummaryData>  $exercises
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $source,
        public string $collectionType,
        public string $collectionKey,
        public int $count,
        public array $exercises,
        public array $raw = [],
    ) {}
}
