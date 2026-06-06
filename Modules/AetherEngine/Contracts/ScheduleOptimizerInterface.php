<?php

namespace Modules\AetherEngine\Contracts;

use Modules\AetherEngine\Data\NutritionDayPlan;
use Modules\AetherEngine\Data\WeeklySchedule;
use Modules\AetherEngine\Data\WorkoutDayPlan;
use Modules\AetherEngine\Models\AetherUserProfile;

interface ScheduleOptimizerInterface
{
    /**
     * @param  array<int, WorkoutDayPlan>  $workoutDays
     * @param  array<int, NutritionDayPlan>  $nutritionDays
     */
    public function optimize(AetherUserProfile $profile, array $workoutDays, array $nutritionDays): WeeklySchedule;
}
