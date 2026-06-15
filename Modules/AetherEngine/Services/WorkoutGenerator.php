<?php

namespace Modules\AetherEngine\Services;

use Modules\AetherEngine\Contracts\WorkoutGeneratorInterface;
use Modules\AetherEngine\Data\ExercisePrescription;
use Modules\AetherEngine\Data\WorkoutDayPlan;
use Modules\AetherEngine\Enums\CoachingTone;
use Modules\AetherEngine\Enums\MuscleGroup;
use Modules\AetherEngine\Enums\PrimaryGoal;
use Modules\AetherEngine\Enums\SessionDuration;
use Modules\AetherEngine\Enums\TrainingExperience;
use Modules\AetherEngine\Enums\WorkoutSplit;
use Modules\AetherEngine\Models\AetherExercise;
use Modules\AetherEngine\Models\AetherUserProfile;

class WorkoutGenerator implements WorkoutGeneratorInterface
{
    public function __construct(
        private ExerciseLibrary $exerciseLibrary,
        private PeriodizationCalculator $periodization,
    ) {}

    /**
     * @return array{split: WorkoutSplit, days: array<int, WorkoutDayPlan>}
     */
    public function generate(AetherUserProfile $profile, int $weekNumber = 1): array
    {
        $split = $this->resolveSplit($profile);
        $volume = $this->sessionVolume($profile->session_duration, $profile->primary_goal, $weekNumber);
        $phase = $this->periodization->forWeek($weekNumber);
        $dayTemplates = $this->dayTemplates($split, $profile->training_days_per_week);

        $days = [];

        foreach ($dayTemplates as $index => $template) {
            $exercises = [];

            foreach ($template['groups'] as $group) {
                $picked = $this->pickExercise($profile, $group);

                if ($picked === null) {
                    continue;
                }

                $exercises[] = new ExercisePrescription(
                    slug: $picked->slug,
                    name: $picked->name,
                    muscleGroup: $picked->muscle_group->value,
                    sets: $volume['sets'],
                    reps: $volume['reps'],
                    restSeconds: $volume['rest'],
                    notes: $picked->instructions,
                    alternativeSlugs: $picked->alternative_slugs ?? [],
                    rpe: $this->adjustedRpe($picked->rpe_range ?? '6-8', $profile->primary_goal, $weekNumber),
                    tempo: $picked->tempo ?? '2-0-2-0',
                    defaultWeightKg: $this->defaultWeightForExperience($picked, $profile->training_experience),
                );
            }

            $days[] = new WorkoutDayPlan(
                dayIndex: $index + 1,
                label: $template['label'],
                focus: trim($template['focus'].' · '.$phase['intensity_note']),
                exercises: $exercises,
                motivation: $this->defaultMotivation($profile, $index + 1),
            );
        }

        return [
            'split' => $split,
            'days' => $days,
        ];
    }

    private function resolveSplit(AetherUserProfile $profile): WorkoutSplit
    {
        $days = $profile->training_days_per_week;

        return match ($profile->training_experience) {
            TrainingExperience::Beginner => WorkoutSplit::FullBody,
            TrainingExperience::Intermediate => $days >= 5 ? WorkoutSplit::PushPullLegs : WorkoutSplit::UpperLower,
            TrainingExperience::Advanced, TrainingExperience::Elite => $days >= 5 ? WorkoutSplit::PushPullLegs : WorkoutSplit::UpperLower,
        };
    }

    /**
     * @return array{sets: int, reps: string, rest: int}
     */
    /**
     * @return array{sets: int, reps: string, rest: int}
     */
    private function sessionVolume(SessionDuration $duration, PrimaryGoal $goal, int $weekNumber): array
    {
        $baseSets = match ($duration) {
            SessionDuration::TenToTwenty => 2,
            SessionDuration::TwentyToThirty => 3,
            SessionDuration::ThirtyToFortyFive => 3,
            SessionDuration::FortyFiveToSixty => 4,
            SessionDuration::SixtyPlus => 4,
        };

        [$reps, $rest] = match ($goal) {
            PrimaryGoal::Strength => ['4-6', 150],
            PrimaryGoal::Endurance => ['15-20', 45],
            PrimaryGoal::FatLoss => ['12-15', 60],
            default => ['8-12', 90],
        };

        return [
            'sets' => $this->periodization->adjustedSets($baseSets, $weekNumber),
            'reps' => $reps,
            'rest' => $rest,
        ];
    }

    /**
     * @return array<int, array{label: string, focus: string, groups: array<int, MuscleGroup>}>
     */
    private function dayTemplates(WorkoutSplit $split, int $daysPerWeek): array
    {
        $templates = match ($split) {
            WorkoutSplit::FullBody => [
                ['label' => 'Foundation A', 'focus' => 'Full body strength', 'groups' => [MuscleGroup::Quads, MuscleGroup::Chest, MuscleGroup::Back, MuscleGroup::Core]],
                ['label' => 'Foundation B', 'focus' => 'Full body hypertrophy', 'groups' => [MuscleGroup::Hamstrings, MuscleGroup::Shoulders, MuscleGroup::Glutes, MuscleGroup::Core]],
                ['label' => 'Foundation C', 'focus' => 'Full body conditioning', 'groups' => [MuscleGroup::FullBody, MuscleGroup::Cardio, MuscleGroup::Core, MuscleGroup::Calves]],
            ],
            WorkoutSplit::UpperLower => [
                ['label' => 'Upper A', 'focus' => 'Push & pull strength', 'groups' => [MuscleGroup::Chest, MuscleGroup::Back, MuscleGroup::Shoulders, MuscleGroup::Triceps, MuscleGroup::Biceps]],
                ['label' => 'Lower A', 'focus' => 'Leg strength', 'groups' => [MuscleGroup::Quads, MuscleGroup::Hamstrings, MuscleGroup::Glutes, MuscleGroup::Calves, MuscleGroup::Core]],
                ['label' => 'Upper B', 'focus' => 'Upper hypertrophy', 'groups' => [MuscleGroup::Back, MuscleGroup::Chest, MuscleGroup::Shoulders, MuscleGroup::Biceps, MuscleGroup::Triceps]],
                ['label' => 'Lower B', 'focus' => 'Leg hypertrophy', 'groups' => [MuscleGroup::Glutes, MuscleGroup::Quads, MuscleGroup::Hamstrings, MuscleGroup::Calves, MuscleGroup::Core]],
            ],
            WorkoutSplit::PushPullLegs => [
                ['label' => 'Push', 'focus' => 'Chest, shoulders, triceps', 'groups' => [MuscleGroup::Chest, MuscleGroup::Shoulders, MuscleGroup::Triceps, MuscleGroup::Core]],
                ['label' => 'Pull', 'focus' => 'Back & biceps', 'groups' => [MuscleGroup::Back, MuscleGroup::Biceps, MuscleGroup::Core, MuscleGroup::Calves]],
                ['label' => 'Legs', 'focus' => 'Quads, hamstrings, glutes', 'groups' => [MuscleGroup::Quads, MuscleGroup::Hamstrings, MuscleGroup::Glutes, MuscleGroup::Calves]],
            ],
            WorkoutSplit::BroSplit => [
                ['label' => 'Chest', 'focus' => 'Chest & triceps', 'groups' => [MuscleGroup::Chest, MuscleGroup::Triceps, MuscleGroup::Shoulders]],
                ['label' => 'Back', 'focus' => 'Back & biceps', 'groups' => [MuscleGroup::Back, MuscleGroup::Biceps, MuscleGroup::Core]],
                ['label' => 'Legs', 'focus' => 'Lower body', 'groups' => [MuscleGroup::Quads, MuscleGroup::Hamstrings, MuscleGroup::Glutes, MuscleGroup::Calves]],
            ],
        };

        return array_slice($templates, 0, max(1, min($daysPerWeek, count($templates))));
    }

    private function pickExercise(AetherUserProfile $profile, MuscleGroup $group): ?AetherExercise
    {
        $candidates = $this->exerciseLibrary->forProfile($profile, $group);

        if ($candidates->isEmpty() && $group !== MuscleGroup::FullBody) {
            $candidates = $this->exerciseLibrary->forProfile($profile, MuscleGroup::FullBody);
        }

        $favorites = collect($profile->favorite_exercises ?? [])->map(fn (string $v): string => strtolower($v));
        $favorite = $candidates->first(fn (AetherExercise $exercise): bool => $favorites->contains(strtolower($exercise->slug)));

        return $favorite ?? $candidates->first();
    }

    private function defaultMotivation(AetherUserProfile $profile, int $dayNumber): string
    {
        return match ($profile->coaching_tone) {
            CoachingTone::ToughLove => "Day {$dayNumber}: Show up. Execute. No excuses.",
            CoachingTone::Technical => "Day {$dayNumber}: Track loads and RPE. Progressive overload drives adaptation.",
            CoachingTone::Gentle => "Day {$dayNumber}: Consistency beats intensity. You are rebuilding with care.",
        };
    }

    /**
     * Shift RPE range up by 1 point in later weeks to encode progressive overload.
     */
    private function adjustedRpe(string $rpeRange, PrimaryGoal $goal, int $weekNumber): string
    {
        [$low, $high] = array_map('intval', explode('-', $rpeRange));

        $goalShift = match ($goal) {
            PrimaryGoal::Strength => 1,
            PrimaryGoal::MuscleGain => 0,
            default => -1,
        };

        $weekShift = min($weekNumber - 1, 2);
        $low = min(10, $low + $goalShift + $weekShift);
        $high = min(10, $high + $goalShift + $weekShift);

        return "{$low}-{$high}";
    }

    private function defaultWeightForExperience(AetherExercise $exercise, TrainingExperience $experience): ?float
    {
        $weight = match ($experience) {
            TrainingExperience::Beginner => $exercise->default_weight_beginner_kg,
            TrainingExperience::Intermediate => $exercise->default_weight_intermediate_kg,
            TrainingExperience::Advanced, TrainingExperience::Elite => $exercise->default_weight_advanced_kg,
        };

        return $weight > 0 ? $weight : null;
    }
}
