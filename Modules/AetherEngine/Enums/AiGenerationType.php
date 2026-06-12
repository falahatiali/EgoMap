<?php

namespace Modules\AetherEngine\Enums;

enum AiGenerationType: string
{
    case FullProgram = 'full_program';
    case WorkoutDay = 'workout_day';
    case MealDay = 'meal_day';
    case ExerciseSwap = 'exercise_swap';
    case WeeklyRevision = 'weekly_revision';
}
