<?php

namespace Modules\AetherEngine\Services;

use Illuminate\Support\Collection;
use Modules\AetherEngine\Enums\MuscleGroup;
use Modules\AetherEngine\Models\AetherExercise;
use Modules\AetherEngine\Models\AetherProgramExercise;
use Modules\AetherEngine\Models\AetherUserProfile;

class ExerciseSubstitutionService
{
    public function __construct(private ExerciseLibrary $exerciseLibrary) {}

    /**
     * @return Collection<int, AetherExercise>
     */
    public function suggestionsFor(
        AetherProgramExercise $programExercise,
        AetherUserProfile $profile,
        int $limit = 6,
    ): Collection {
        $source = AetherExercise::query()->where('slug', $programExercise->slug)->first();

        $muscleGroup = $source?->muscle_group
            ?? MuscleGroup::tryFrom($programExercise->muscle_group)
            ?? MuscleGroup::FullBody;
        $movementPattern = $source?->movement_pattern ?? 'compound';
        $difficulty = $source?->difficulty ?? 2;

        return $this->exerciseLibrary
            ->forProfile($profile, $muscleGroup)
            ->reject(fn (AetherExercise $candidate): bool => $candidate->slug === $programExercise->slug)
            ->filter(function (AetherExercise $candidate) use ($movementPattern, $difficulty): bool {
                if ($candidate->movement_pattern !== $movementPattern) {
                    return false;
                }

                return abs($candidate->difficulty - $difficulty) <= 1;
            })
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, AetherExercise>
     */
    public function searchForProfile(AetherUserProfile $profile, string $query, int $limit = 12): Collection
    {
        $normalized = strtolower(trim($query));

        if ($normalized === '') {
            return collect();
        }

        return $this->exerciseLibrary
            ->forProfile($profile)
            ->filter(fn (AetherExercise $exercise): bool => str_contains(strtolower($exercise->name), $normalized)
                || str_contains($exercise->slug, str_replace(' ', '-', $normalized)))
            ->take($limit)
            ->values();
    }
}
