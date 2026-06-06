<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AetherEngine\Contracts\NutritionGeneratorInterface;
use Modules\AetherEngine\Contracts\ProgramEnrichmentInterface;
use Modules\AetherEngine\Contracts\ScheduleOptimizerInterface;
use Modules\AetherEngine\Contracts\WorkoutGeneratorInterface;
use Modules\AetherEngine\Data\GeneratedProgramPayload;
use Modules\AetherEngine\Data\NutritionDayPlan;
use Modules\AetherEngine\Enums\ProgramStatus;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherUserProfile;

class AetherEngineService
{
    public function __construct(
        private MetabolicCalculator $metabolicCalculator,
        private WorkoutGeneratorInterface $workoutGenerator,
        private NutritionGeneratorInterface $nutritionGenerator,
        private ScheduleOptimizerInterface $scheduleOptimizer,
        private ProgramEnrichmentInterface $programEnrichment,
        private AetherProgramPersistenceService $programPersistence,
    ) {}

    public function generate(AetherUserProfile $profile, int $weekNumber = 1): AetherGeneratedProgram
    {
        if (! $profile->isQuestionnaireComplete()) {
            throw new \InvalidArgumentException('Cannot generate program: questionnaire is incomplete.');
        }

        $payload = $this->buildPayload($profile);

        return DB::transaction(function () use ($profile, $payload, $weekNumber): AetherGeneratedProgram {
            $version = (int) AetherGeneratedProgram::query()
                ->where('user_id', $profile->user_id)
                ->max('version') + 1;

            AetherGeneratedProgram::query()
                ->where('user_id', $profile->user_id)
                ->where('status', ProgramStatus::Active)
                ->update(['status' => ProgramStatus::Archived]);

            $program = AetherGeneratedProgram::query()->create([
                'user_id' => $profile->user_id,
                'aether_user_profile_id' => $profile->id,
                'version' => $version,
                'week_number' => $weekNumber,
                'status' => ProgramStatus::Active,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(6)->toDateString(),
            ]);

            return $this->programPersistence->persist($program, $payload);
        });
    }

    public function generateForUser(User $user, int $weekNumber = 1): AetherGeneratedProgram
    {
        $profile = AetherUserProfile::query()->where('user_id', $user->id)->firstOrFail();

        return $this->generate($profile, $weekNumber);
    }

    public function buildPayload(AetherUserProfile $profile): GeneratedProgramPayload
    {
        $metabolic = $this->metabolicCalculator->calculate($profile);
        $workout = $this->workoutGenerator->generate($profile);
        $nutritionDays = $this->nutritionGenerator->generate($profile, $metabolic);
        $schedule = $this->scheduleOptimizer->optimize($profile, $workout['days'], $nutritionDays);

        $payload = new GeneratedProgramPayload(
            metabolic: $metabolic,
            split: $workout['split'],
            workoutDays: $workout['days'],
            nutritionDays: $nutritionDays,
            schedule: $schedule,
            shoppingListSummary: $this->buildShoppingListSummary($nutritionDays),
        );

        $narrative = $this->programEnrichment->enrich($profile, $payload);

        return new GeneratedProgramPayload(
            metabolic: $metabolic,
            split: $workout['split'],
            workoutDays: $workout['days'],
            nutritionDays: $nutritionDays,
            schedule: $schedule,
            narrative: $narrative,
            shoppingListSummary: $payload->shoppingListSummary,
        );
    }

    /**
     * @param  array<int, NutritionDayPlan>  $nutritionDays
     */
    private function buildShoppingListSummary(array $nutritionDays): string
    {
        $ingredients = collect($nutritionDays)
            ->flatMap(fn ($day) => collect($day->meals)->flatMap(fn ($meal) => $meal->ingredients))
            ->unique()
            ->sort()
            ->values();

        return $ingredients->take(20)->implode(', ');
    }

    public function activeProgramForUser(User $user): ?AetherGeneratedProgram
    {
        return AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->where('user_id', $user->id)
            ->where('status', ProgramStatus::Active)
            ->latest('id')
            ->first();
    }
}
