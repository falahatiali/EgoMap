<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Contracts\ScheduleOptimizerInterface;
use Modules\AetherEngine\Data\NutritionDayPlan;
use Modules\AetherEngine\Data\WeeklySchedule;
use Modules\AetherEngine\Data\WorkoutDayPlan;
use Modules\AetherEngine\Enums\WorkoutTimePreference;
use Modules\AetherEngine\Models\AetherUserProfile;

class ScheduleOptimizer implements ScheduleOptimizerInterface
{
    /**
     * @param  array<int, WorkoutDayPlan>  $workoutDays
     * @param  array<int, NutritionDayPlan>  $nutritionDays
     */
    public function optimize(AetherUserProfile $profile, array $workoutDays, array $nutritionDays): WeeklySchedule
    {
        $workoutCount = count($workoutDays);
        $preferredStart = $this->preferredStartWeekday($profile->preferred_workout_time);
        $workoutWeekdays = $this->distributeWorkoutDays($workoutCount, $preferredStart);
        $allWeekdays = range(1, 7);
        $restWeekdays = array_values(array_diff($allWeekdays, array_keys($workoutWeekdays)));

        $mealTimingNotes = [];

        foreach ($workoutWeekdays as $weekday => $dayIndex) {
            $mealTimingNotes[$weekday] = match ($profile->preferred_workout_time) {
                WorkoutTimePreference::Morning => 'Train fasted or light snack 60 min before; protein-rich breakfast after.',
                WorkoutTimePreference::Afternoon => 'Balanced lunch 2h before; post-workout snack within 60 min.',
                WorkoutTimePreference::Evening => 'Carb-focused lunch; lighter dinner 2h post-session.',
                default => 'Eat protein within 2 hours of training.',
            };
        }

        return new WeeklySchedule(
            workoutWeekdays: $workoutWeekdays,
            restWeekdays: $restWeekdays,
            mealTimingNotes: $mealTimingNotes,
        );
    }

    /**
     * @return array<int, int>
     */
    private function distributeWorkoutDays(int $count, int $startWeekday): array
    {
        $count = max(1, min(6, $count));
        $spacing = (int) floor(7 / $count);
        $schedule = [];
        $weekday = $startWeekday;

        for ($i = 0; $i < $count; $i++) {
            $schedule[$weekday] = $i + 1;
            $weekday += $spacing;

            if ($weekday > 7) {
                $weekday -= 7;
            }

            if (isset($schedule[$weekday])) {
                $weekday = min(7, $weekday + 1);
            }
        }

        ksort($schedule);

        return $schedule;
    }

    private function preferredStartWeekday(?WorkoutTimePreference $preference): int
    {
        return match ($preference) {
            WorkoutTimePreference::Morning => 1,
            WorkoutTimePreference::Afternoon => 3,
            WorkoutTimePreference::Evening => 5,
            default => 2,
        };
    }
}
