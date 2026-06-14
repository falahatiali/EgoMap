<?php

namespace Modules\AetherEngine\Data\External;

readonly class ExerciseGymGifsDbGlobalIndexData
{
    /**
     * @param  list<string>  $languages
     * @param  array<string, array<string, int>>  $totals
     * @param  array<string, string>  $endpoints
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $name,
        public string $baseUrl,
        public string $generatedAt,
        public array $languages,
        public string $defaultLanguage,
        public array $totals,
        public array $endpoints,
        public array $raw = [],
    ) {}
}
