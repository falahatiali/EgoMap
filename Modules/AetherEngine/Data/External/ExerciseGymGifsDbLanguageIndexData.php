<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseGymGifsDbLanguageIndexData
{
    /**
     * @param  array<string, int>  $totals
     * @param  array<string, string>  $endpoints
     * @param  list<ExerciseNamedCountData>  $muscles
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $name,
        public string $language,
        public string $baseUrl,
        public string $generatedAt,
        public array $totals,
        public array $endpoints,
        public array $muscles,
        public array $raw = [],
    ) {}
}
