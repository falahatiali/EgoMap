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
        public ?string $rpe = null,
        public ?string $tempo = null,
        public ?float $defaultWeightKg = null,
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
            'rpe' => $this->rpe,
            'tempo' => $this->tempo,
            'default_weight_kg' => $this->defaultWeightKg,
        ];
    }
}
