<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Contracts\NutritionGeneratorInterface;
use Modules\AetherEngine\Data\MealSlot;
use Modules\AetherEngine\Data\MetabolicTargets;
use Modules\AetherEngine\Data\NutritionDayPlan;
use Modules\AetherEngine\Enums\MealType;
use Modules\AetherEngine\Models\AetherMealTemplate;
use Modules\AetherEngine\Models\AetherUserProfile;

class NutritionGenerator implements NutritionGeneratorInterface
{
    public function __construct(private MealTemplateLibrary $mealLibrary) {}

    /**
     * @return array<int, NutritionDayPlan>
     */
    public function generate(AetherUserProfile $profile, MetabolicTargets $targets): array
    {
        $mealTypes = [
            MealType::Breakfast,
            MealType::Lunch,
            MealType::Dinner,
            MealType::Snack,
        ];

        $days = [];

        for ($day = 1; $day <= 7; $day++) {
            $meals = [];
            $totalCalories = 0;
            $totalProtein = 0;
            $totalCarbs = 0;
            $totalFat = 0;

            foreach ($mealTypes as $mealType) {
                $template = $this->selectMeal($profile, $mealType, $day);
                $scaled = $this->scaleMeal($template, $targets, count($mealTypes));

                $meals[] = $scaled;
                $totalCalories += $scaled->calories;
                $totalProtein += $scaled->proteinGrams;
                $totalCarbs += $scaled->carbGrams;
                $totalFat += $scaled->fatGrams;
            }

            $days[] = new NutritionDayPlan(
                dayIndex: $day,
                meals: $meals,
                totalCalories: $totalCalories,
                totalProtein: $totalProtein,
                totalCarbs: $totalCarbs,
                totalFat: $totalFat,
                tip: $this->dailyTip($profile, $day),
            );
        }

        return $days;
    }

    private function selectMeal(AetherUserProfile $profile, MealType $mealType, int $dayIndex): AetherMealTemplate
    {
        $pool = $this->mealLibrary->forProfile($profile, $mealType);

        if ($pool->isEmpty()) {
            return $this->fallbackMeal($mealType);
        }

        return $pool->get(($dayIndex - 1) % $pool->count());
    }

    private function scaleMeal(AetherMealTemplate $template, MetabolicTargets $targets, int $mealsPerDay): MealSlot
    {
        $targetPerMeal = (int) round($targets->targetCalories / $mealsPerDay);
        $ratio = $template->calories > 0 ? $targetPerMeal / $template->calories : 1;
        $ratio = max(0.75, min(1.35, $ratio));

        return new MealSlot(
            mealType: $template->meal_type,
            name: $template->name,
            calories: (int) round($template->calories * $ratio),
            proteinGrams: (int) round($template->protein_g * $ratio),
            carbGrams: (int) round($template->carbs_g * $ratio),
            fatGrams: (int) round($template->fat_g * $ratio),
            ingredients: $template->ingredients ?? [],
            instructions: $template->instructions,
            prepMinutes: $template->prep_time_minutes,
        );
    }

    private function fallbackMeal(MealType $mealType): AetherMealTemplate
    {
        $meal = new AetherMealTemplate([
            'slug' => 'fallback-'.$mealType->value,
            'name' => ucfirst($mealType->value).' — balanced plate',
            'meal_type' => $mealType->value,
            'dietary_tags' => ['omnivore'],
            'calories' => 500,
            'protein_g' => 30,
            'carbs_g' => 50,
            'fat_g' => 15,
            'ingredients' => ['lean protein', 'complex carbs', 'vegetables', 'healthy fat'],
            'instructions' => 'Combine portions to match your macro targets.',
            'prep_time_minutes' => 15,
            'is_active' => true,
        ]);
        $meal->meal_type = $mealType;

        return $meal;
    }

    private function dailyTip(AetherUserProfile $profile, int $day): string
    {
        $tips = [
            'Prioritize protein at each meal to support recovery.',
            'Hydration affects performance — aim for 2.5–3L water daily.',
            'Sleep is training — protect your '.$profile->sleep_hours.'h target tonight.',
            'Prep one meal ahead to reduce decision fatigue.',
            'Post-workout carbs help replenish glycogen.',
            'Fiber from vegetables supports digestion and satiety.',
            'Consistency this week matters more than perfection.',
        ];

        return $tips[($day - 1) % count($tips)];
    }
}
