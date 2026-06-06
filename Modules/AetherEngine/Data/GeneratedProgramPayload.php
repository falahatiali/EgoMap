<?php

namespace Modules\AetherEngine\Data;

use Modules\AetherEngine\Enums\WorkoutSplit;

readonly class GeneratedProgramPayload
{
    /**
     * @param  array<int, WorkoutDayPlan>  $workoutDays
     * @param  array<int, NutritionDayPlan>  $nutritionDays
     * @param  array<string, mixed>  $narrative
     */
    public function __construct(
        public MetabolicTargets $metabolic,
        public WorkoutSplit $split,
        public array $workoutDays,
        public array $nutritionDays,
        public WeeklySchedule $schedule,
        public array $narrative = [],
        public ?string $shoppingListSummary = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'metabolic' => $this->metabolic->toArray(),
            'split' => $this->split->value,
            'workout_days' => array_map(
                static fn (WorkoutDayPlan $day): array => $day->toArray(),
                $this->workoutDays,
            ),
            'nutrition_days' => array_map(
                static fn (NutritionDayPlan $day): array => $day->toArray(),
                $this->nutritionDays,
            ),
            'schedule' => $this->schedule->toArray(),
            'narrative' => $this->narrative,
            'shopping_list_summary' => $this->shoppingListSummary,
        ];
    }
}
