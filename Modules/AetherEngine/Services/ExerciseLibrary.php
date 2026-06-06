<?php

namespace Modules\AetherEngine\Services;

use Illuminate\Support\Collection;
use Modules\AetherEngine\Enums\EquipmentAccess;
use Modules\AetherEngine\Enums\MuscleGroup;
use Modules\AetherEngine\Models\AetherExercise;
use Modules\AetherEngine\Models\AetherUserProfile;

class ExerciseLibrary
{
    /**
     * @var array<string, array<int, string>>
     */
    private const EQUIPMENT_COMPATIBILITY = [
        EquipmentAccess::FullGym->value => ['barbell', 'dumbbell', 'machine', 'cable', 'bodyweight', 'cardio', 'band', 'kettlebell'],
        EquipmentAccess::HomeGym->value => ['dumbbell', 'bench', 'bodyweight', 'band', 'kettlebell'],
        EquipmentAccess::ResistanceBands->value => ['band', 'bodyweight'],
        EquipmentAccess::BodyweightOnly->value => ['bodyweight'],
        EquipmentAccess::Outdoor->value => ['bodyweight', 'cardio', 'band'],
    ];

    /**
     * @return Collection<int, AetherExercise>
     */
    public function forProfile(AetherUserProfile $profile, ?MuscleGroup $muscleGroup = null): Collection
    {
        $allowedEquipment = self::EQUIPMENT_COMPATIBILITY[$profile->equipment->value] ?? ['bodyweight'];
        $injuryTags = $profile->resolvedInjuryTags();
        $disliked = collect($profile->disliked_exercises ?? [])->map(fn (string $v): string => strtolower($v));

        return AetherExercise::query()
            ->where('is_active', true)
            ->when($muscleGroup !== null, fn ($query) => $query->where('muscle_group', $muscleGroup->value))
            ->orderBy('difficulty')
            ->get()
            ->filter(function (AetherExercise $exercise) use ($allowedEquipment, $injuryTags, $disliked): bool {
                if ($disliked->contains(strtolower($exercise->slug)) || $disliked->contains(strtolower($exercise->name))) {
                    return false;
                }

                $equipment = $exercise->equipment_required ?? [];

                if ($equipment !== [] && ! collect($equipment)->intersect($allowedEquipment)->isNotEmpty()) {
                    return false;
                }

                $contraindications = $exercise->contraindications ?? [];

                return collect($injuryTags)->intersect($contraindications)->isEmpty();
            })
            ->values();
    }
}
