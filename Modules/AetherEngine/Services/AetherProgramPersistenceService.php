<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Data\ExercisePrescription;
use Modules\AetherEngine\Data\GeneratedProgramPayload;
use Modules\AetherEngine\Data\MealSlot;
use Modules\AetherEngine\Data\NutritionDayPlan;
use Modules\AetherEngine\Data\ProgramCoachNarrative;
use Modules\AetherEngine\Data\WorkoutDayPlan;
use Modules\AetherEngine\Enums\ProgramScheduleEntryType;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramExercise;
use Modules\AetherEngine\Models\AetherProgramMeal;
use Modules\AetherEngine\Models\AetherProgramMealIngredient;
use Modules\AetherEngine\Models\AetherProgramNutritionDay;
use Modules\AetherEngine\Models\AetherProgramScheduleEntry;
use Modules\AetherEngine\Models\AetherProgramWorkoutDay;

class AetherProgramPersistenceService
{
    public function persist(AetherGeneratedProgram $program, GeneratedProgramPayload $payload): AetherGeneratedProgram
    {
        $coach = ProgramCoachNarrative::fromEnrichmentArray($payload->narrative);

        $program->update(array_merge(
            [
                'split' => $payload->split->value,
                'shopping_list_summary' => $payload->shoppingListSummary,
                'metabolic_bmr' => $payload->metabolic->bmr,
                'metabolic_tdee' => $payload->metabolic->tdee,
                'metabolic_target_calories' => $payload->metabolic->targetCalories,
                'metabolic_protein_grams' => $payload->metabolic->proteinGrams,
                'metabolic_fat_grams' => $payload->metabolic->fatGrams,
                'metabolic_carb_grams' => $payload->metabolic->carbGrams,
                'metabolic_protein_g_per_kg' => $payload->metabolic->proteinGPerKg,
                'metabolic_activity_multiplier' => $payload->metabolic->activityMultiplier,
            ],
            $coach->toProgramColumns(),
        ));

        $this->persistSchedule($program, $payload);
        $this->persistWorkoutDays($program, $payload->workoutDays);
        $this->persistNutritionDays($program, $payload->nutritionDays);

        return $program->fresh()->load([
            'scheduleEntries',
            'workoutDays.exercises',
            'nutritionDays.meals.ingredients',
        ]);
    }

    private function persistSchedule(AetherGeneratedProgram $program, GeneratedProgramPayload $payload): void
    {
        $program->scheduleEntries()->delete();

        foreach ($payload->schedule->workoutWeekdays as $isoWeekday => $workoutDayIndex) {
            AetherProgramScheduleEntry::query()->create([
                'aether_generated_program_id' => $program->id,
                'iso_weekday' => (int) $isoWeekday,
                'entry_type' => ProgramScheduleEntryType::Workout,
                'workout_day_index' => (int) $workoutDayIndex,
                'meal_timing_note' => $payload->schedule->mealTimingNotes[(int) $isoWeekday] ?? null,
            ]);
        }

        foreach ($payload->schedule->restWeekdays as $isoWeekday) {
            AetherProgramScheduleEntry::query()->create([
                'aether_generated_program_id' => $program->id,
                'iso_weekday' => (int) $isoWeekday,
                'entry_type' => ProgramScheduleEntryType::Rest,
                'workout_day_index' => null,
                'meal_timing_note' => $payload->schedule->mealTimingNotes[(int) $isoWeekday] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, WorkoutDayPlan>  $workoutDays
     */
    private function persistWorkoutDays(AetherGeneratedProgram $program, array $workoutDays): void
    {
        $program->workoutDays()->with('exercises')->get()->each(function (AetherProgramWorkoutDay $day): void {
            $day->exercises()->delete();
        });
        $program->workoutDays()->delete();

        foreach ($workoutDays as $dayPlan) {
            $day = AetherProgramWorkoutDay::query()->create([
                'aether_generated_program_id' => $program->id,
                'day_index' => $dayPlan->dayIndex,
                'label' => $dayPlan->label,
                'focus' => $dayPlan->focus,
                'warmup' => $dayPlan->warmup,
                'cooldown' => $dayPlan->cooldown,
                'motivation' => $dayPlan->motivation,
            ]);

            foreach ($dayPlan->exercises as $sortOrder => $exercise) {
                $this->persistExercise($day, $exercise, $sortOrder);
            }
        }
    }

    private function persistExercise(
        AetherProgramWorkoutDay $day,
        ExercisePrescription $exercise,
        int $sortOrder,
    ): void {
        AetherProgramExercise::query()->create([
            'aether_program_workout_day_id' => $day->id,
            'sort_order' => $sortOrder,
            'slug' => $exercise->slug,
            'name' => $exercise->name,
            'muscle_group' => $exercise->muscleGroup,
            'sets' => $exercise->sets,
            'reps' => $exercise->reps,
            'rest_seconds' => $exercise->restSeconds,
            'notes' => $exercise->notes,
            'alternative_slugs' => $exercise->alternativeSlugs !== [] ? $exercise->alternativeSlugs : null,
        ]);
    }

    /**
     * @param  array<int, NutritionDayPlan>  $nutritionDays
     */
    private function persistNutritionDays(AetherGeneratedProgram $program, array $nutritionDays): void
    {
        $program->nutritionDays()->with('meals.ingredients')->get()->each(function (AetherProgramNutritionDay $day): void {
            $day->meals->each(function (AetherProgramMeal $meal): void {
                $meal->ingredients()->delete();
            });
            $day->meals()->delete();
        });
        $program->nutritionDays()->delete();

        foreach ($nutritionDays as $dayPlan) {
            $day = AetherProgramNutritionDay::query()->create([
                'aether_generated_program_id' => $program->id,
                'day_index' => $dayPlan->dayIndex,
                'total_calories' => $dayPlan->totalCalories,
                'total_protein' => $dayPlan->totalProtein,
                'total_carbs' => $dayPlan->totalCarbs,
                'total_fat' => $dayPlan->totalFat,
                'tip' => $dayPlan->tip,
            ]);

            foreach ($dayPlan->meals as $sortOrder => $meal) {
                $this->persistMeal($day, $meal, $sortOrder);
            }
        }
    }

    private function persistMeal(
        AetherProgramNutritionDay $day,
        MealSlot $meal,
        int $sortOrder,
    ): void {
        $mealModel = AetherProgramMeal::query()->create([
            'aether_program_nutrition_day_id' => $day->id,
            'sort_order' => $sortOrder,
            'meal_type' => $meal->mealType->value,
            'name' => $meal->name,
            'calories' => $meal->calories,
            'protein_grams' => $meal->proteinGrams,
            'carb_grams' => $meal->carbGrams,
            'fat_grams' => $meal->fatGrams,
            'instructions' => $meal->instructions,
            'prep_minutes' => $meal->prepMinutes,
        ]);

        foreach ($meal->ingredients as $ingredientOrder => $ingredient) {
            AetherProgramMealIngredient::query()->create([
                'aether_program_meal_id' => $mealModel->id,
                'sort_order' => $ingredientOrder,
                'ingredient' => $ingredient,
            ]);
        }
    }
}
