<?php

namespace Modules\AetherEngine\Data;

readonly class WeeklySchedule
{
    /**
     * @param  array<int, int>  $workoutWeekdays  ISO weekday (1=Mon) => workout day_index
     * @param  array<int, int>  $restWeekdays
     * @param  array<int, string>  $mealTimingNotes  weekday => note
     */
    public function __construct(
        public array $workoutWeekdays,
        public array $restWeekdays,
        public array $mealTimingNotes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workout_weekdays' => $this->workoutWeekdays,
            'rest_weekdays' => $this->restWeekdays,
            'meal_timing_notes' => $this->mealTimingNotes,
        ];
    }
}
