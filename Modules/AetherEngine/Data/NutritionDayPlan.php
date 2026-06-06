<?php

namespace Modules\AetherEngine\Data;

readonly class NutritionDayPlan
{
    /**
     * @param  array<int, MealSlot>  $meals
     */
    public function __construct(
        public int $dayIndex,
        public array $meals,
        public int $totalCalories,
        public int $totalProtein,
        public int $totalCarbs,
        public int $totalFat,
        public ?string $tip = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'day_index' => $this->dayIndex,
            'total_calories' => $this->totalCalories,
            'total_protein' => $this->totalProtein,
            'total_carbs' => $this->totalCarbs,
            'total_fat' => $this->totalFat,
            'tip' => $this->tip,
            'meals' => array_map(
                static fn (MealSlot $meal): array => $meal->toArray(),
                $this->meals,
            ),
        ];
    }
}
