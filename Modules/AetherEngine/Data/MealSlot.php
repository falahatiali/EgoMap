<?php

namespace Modules\AetherEngine\Data;

use Modules\AetherEngine\Enums\MealType;

readonly class MealSlot
{
    /**
     * @param  array<int, string>  $ingredients
     */
    public function __construct(
        public MealType $mealType,
        public string $name,
        public int $calories,
        public int $proteinGrams,
        public int $carbGrams,
        public int $fatGrams,
        public array $ingredients,
        public ?string $instructions = null,
        public ?int $prepMinutes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'meal_type' => $this->mealType->value,
            'name' => $this->name,
            'calories' => $this->calories,
            'protein_grams' => $this->proteinGrams,
            'carb_grams' => $this->carbGrams,
            'fat_grams' => $this->fatGrams,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'prep_minutes' => $this->prepMinutes,
        ];
    }
}
