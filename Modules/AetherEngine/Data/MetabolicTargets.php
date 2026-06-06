<?php

namespace Modules\AetherEngine\Data;

readonly class MetabolicTargets
{
    public function __construct(
        public int $bmr,
        public int $tdee,
        public int $targetCalories,
        public int $proteinGrams,
        public int $fatGrams,
        public int $carbGrams,
        public float $proteinGPerKg,
        public float $activityMultiplier,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function toArray(): array
    {
        return [
            'bmr' => $this->bmr,
            'tdee' => $this->tdee,
            'target_calories' => $this->targetCalories,
            'protein_grams' => $this->proteinGrams,
            'fat_grams' => $this->fatGrams,
            'carb_grams' => $this->carbGrams,
            'protein_g_per_kg' => $this->proteinGPerKg,
            'activity_multiplier' => $this->activityMultiplier,
        ];
    }
}
