<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Data\MetabolicTargets;
use Modules\AetherEngine\Enums\Gender;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Models\AetherUserProfile;

class MetabolicCalculator
{
    public function calculate(AetherUserProfile $profile): MetabolicTargets
    {
        $bmr = $this->calculateBmr($profile);
        $activityMultiplier = $this->activityMultiplier($profile->training_days_per_week);
        $tdee = (int) round($bmr * $activityMultiplier);
        $targetCalories = $this->targetCalories($profile, $tdee);
        $proteinGPerKg = $this->proteinGPerKg($profile->primary_goal);
        $proteinGrams = (int) round((float) $profile->weight_kg * $proteinGPerKg);
        $fatGrams = (int) round((float) $profile->weight_kg * (float) config('aether.fat_g_per_kg', 0.9));
        $proteinCalories = $proteinGrams * 4;
        $fatCalories = $fatGrams * 9;
        $carbGrams = max(50, (int) round(($targetCalories - $proteinCalories - $fatCalories) / 4));

        return new MetabolicTargets(
            bmr: $bmr,
            tdee: $tdee,
            targetCalories: $targetCalories,
            proteinGrams: $proteinGrams,
            fatGrams: $fatGrams,
            carbGrams: $carbGrams,
            proteinGPerKg: $proteinGPerKg,
            activityMultiplier: $activityMultiplier,
        );
    }

    private function calculateBmr(AetherUserProfile $profile): int
    {
        $weight = (float) $profile->weight_kg;
        $height = (float) $profile->height_cm;
        $age = $profile->age;

        if ($profile->body_fat_percent !== null) {
            $leanMass = $weight * (1 - ((float) $profile->body_fat_percent / 100));

            return (int) round(370 + (21.6 * $leanMass));
        }

        $base = (10 * $weight) + (6.25 * $height) - (5 * $age);

        return (int) round(match ($profile->gender) {
            Gender::Male => $base + 5,
            Gender::Female => $base - 161,
            Gender::Other => $base - 78,
        });
    }

    private function activityMultiplier(int $trainingDays): float
    {
        return match (true) {
            $trainingDays <= 2 => 1.375,
            $trainingDays <= 4 => 1.55,
            $trainingDays <= 6 => 1.725,
            default => 1.9,
        };
    }

    private function targetCalories(AetherUserProfile $profile, int $tdee): int
    {
        $adjustments = config('aether.calorie_adjustments', []);
        $adjustment = $adjustments[$profile->primary_goal->value] ?? 0;

        if ($profile->estimated_daily_calories !== null && $profile->primary_goal === PrimaryGoal::Health) {
            return $profile->estimated_daily_calories;
        }

        return max(1200, $tdee + $adjustment);
    }

    private function proteinGPerKg(PrimaryGoal $goal): float
    {
        $map = config('aether.protein_g_per_kg', []);

        return (float) ($map[$goal->value] ?? $map['default'] ?? 1.6);
    }
}
