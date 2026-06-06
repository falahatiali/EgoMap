<?php

namespace Modules\AetherEngine\Services;

use Illuminate\Support\Collection;
use Modules\AetherEngine\Enums\CookingAbility;
use Modules\AetherEngine\Enums\DietaryPattern;
use Modules\AetherEngine\Enums\MealType;
use Modules\AetherEngine\Models\AetherMealTemplate;
use Modules\AetherEngine\Models\AetherUserProfile;

class MealTemplateLibrary
{
    /**
     * @return Collection<int, AetherMealTemplate>
     */
    public function forProfile(AetherUserProfile $profile, MealType $mealType): Collection
    {
        $pattern = $profile->dietary_pattern;
        $allergies = collect($profile->allergies ?? [])->map(fn (string $a): string => strtolower($a));
        $maxPrep = $this->maxPrepMinutes($profile->cooking_ability);

        return AetherMealTemplate::query()
            ->where('is_active', true)
            ->where('meal_type', $mealType->value)
            ->orderBy('calories')
            ->get()
            ->filter(function (AetherMealTemplate $meal) use ($pattern, $allergies, $maxPrep): bool {
                if (! $this->matchesDietaryPattern($meal, $pattern)) {
                    return false;
                }

                if ($maxPrep !== null && $meal->prep_time_minutes !== null && $meal->prep_time_minutes > $maxPrep) {
                    return false;
                }

                $ingredients = collect($meal->ingredients ?? [])->map(fn (string $i): string => strtolower($i));

                return $allergies->intersect($ingredients)->isEmpty()
                    && ! $allergies->contains(fn (string $allergy): bool => $ingredients->contains(fn (string $ingredient): bool => str_contains($ingredient, $allergy)));
            })
            ->values();
    }

    private function matchesDietaryPattern(AetherMealTemplate $meal, DietaryPattern $pattern): bool
    {
        $tags = collect($meal->dietary_tags ?? []);

        return match ($pattern) {
            DietaryPattern::Vegan => $tags->contains('vegan'),
            DietaryPattern::Vegetarian => $tags->contains('vegetarian') || $tags->contains('vegan'),
            DietaryPattern::Halal => $tags->contains('halal') || $tags->contains('omnivore'),
            DietaryPattern::Kosher => $tags->contains('kosher') || $tags->contains('omnivore'),
            DietaryPattern::Omnivore, DietaryPattern::Other => true,
        };
    }

    private function maxPrepMinutes(CookingAbility $ability): ?int
    {
        return match ($ability) {
            CookingAbility::Never => 10,
            CookingAbility::Simple => 20,
            CookingAbility::Enjoy => 45,
            CookingAbility::MealPrep => 60,
        };
    }
}
