<?php

namespace Modules\AetherEngine\Data\External;

readonly class PaginatedExerciseSummariesData
{
    /**
     * @param  list<ExerciseSummaryData>  $results
     */
    public function __construct(
        public string $source,
        public int $total,
        public int $limit,
        public int $offset,
        public int $count,
        public array $results,
    ) {}
}
