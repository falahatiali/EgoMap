<?php

namespace Modules\AetherEngine\Data;

readonly class ExercisePrescription
{
    /**
     * @param  array<int, string>  $alternativeSlugs
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $muscleGroup,
        public int $sets,
        public string $reps,
        public int $restSeconds,
        public ?string $notes = null,
        public array $alternativeSlugs = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'muscle_group' => $this->muscleGroup,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'rest_seconds' => $this->restSeconds,
            'notes' => $this->notes,
            'alternatives' => $this->alternativeSlugs,
        ];
    }
}
