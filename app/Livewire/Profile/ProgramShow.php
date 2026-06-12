<?php

namespace App\Livewire\Profile;

use App\Services\Profile\UserAetherProgramHistoryService;
use App\Support\LocaleConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\AetherEngine\Models\AetherExercise;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\AetherEngine\Models\AetherProgramExercise;
use Modules\AetherEngine\Models\AetherProgramExerciseSet;
use Modules\AetherEngine\Models\AetherWorkoutSetLog;
use Modules\AetherEngine\Services\AetherWorkoutLogService;
use Modules\AetherEngine\Services\AetherWorkoutSessionService;
use Modules\AetherEngine\Services\ExerciseMediaResolver;
use Modules\AetherEngine\Services\ExerciseSubstitutionService;
use Modules\AetherEngine\Support\ExerciseSetPrescriptionBuilder;

#[Layout('layouts.app')]
class ProgramShow extends Component
{
    public AetherGeneratedProgram $program;

    public string $activeSection = 'overview';

    public ?int $selectedWorkoutDayId = null;

    public ?int $swapExerciseId = null;

    public string $swapSearchQuery = '';

    public ?int $editExerciseId = null;

    public int $editSets = 3;

    public string $editReps = '10';

    public int $editRest = 90;

    public function mount(string $uuid): void
    {
        $this->program = AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->with(['profile', 'missionEnrollment'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_unless($this->program->user_id === Auth::id(), 403);

        $this->selectedWorkoutDayId = $this->program->workoutDays->first()?->id;
    }

    public function setProgramSection(string $section): void
    {
        if (! in_array($section, ['overview', 'workout', 'nutrition', 'coach'], true)) {
            return;
        }

        $this->activeSection = $section;
    }

    public function selectWorkoutDay(int $dayId): void
    {
        if ($this->program->workoutDays->contains('id', $dayId)) {
            $this->selectedWorkoutDayId = $dayId;
            $this->closeSwapModal();
            $this->editExerciseId = null;
        }
    }

    public function startWorkoutSession(AetherWorkoutSessionService $sessions): void
    {
        $day = $this->selectedWorkoutDay();

        if ($day === null) {
            return;
        }

        $sessions->startOrResume(Auth::user(), $this->program, $day);
    }

    public function toggleWorkoutSet(
        int $exerciseSetId,
        AetherWorkoutLogService $logs,
        ExerciseSetPrescriptionBuilder $setBuilder,
    ): void {
        $day = $this->selectedWorkoutDay();

        if ($day === null) {
            return;
        }

        $exerciseSet = $this->findProgramExerciseSet($exerciseSetId);
        $log = $logs->toggleSet(Auth::user(), $this->program, $exerciseSet, $day);

        $this->dispatch('aether-rest-timer', seconds: $exerciseSet->rest_seconds);

        if ($log->completed) {
            $this->dispatch('mission-saved');
        }
    }

    public function openSwapModal(int $exerciseId): void
    {
        $this->swapExerciseId = $exerciseId;
        $this->swapSearchQuery = '';
    }

    public function closeSwapModal(): void
    {
        $this->swapExerciseId = null;
        $this->swapSearchQuery = '';
    }

    public function selectSwapCandidate(int $exerciseId, string $slug, AetherWorkoutLogService $logs): void
    {
        $candidate = AetherExercise::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->applyExerciseSwap(
            $exerciseId,
            $candidate->slug,
            $candidate->name,
            $candidate->muscle_group->value,
            $logs,
        );
    }

    public function applyExerciseSwap(
        int $exerciseId,
        string $slug,
        string $name,
        string $muscleGroup,
        AetherWorkoutLogService $logs,
    ): void {
        $exercise = $this->findProgramExercise($exerciseId);
        $logs->applySwap(Auth::user(), $this->program, $exercise, $slug, $name, $muscleGroup);
        $this->closeSwapModal();
        $this->program->load('workoutDays.exercises.prescriptionSets');
    }

    public function openExerciseEdit(int $exerciseId, AetherWorkoutLogService $logs): void
    {
        $exercise = $this->findProgramExercise($exerciseId);
        $display = $logs->displayExercise($exercise, Auth::user());

        $this->editExerciseId = $exerciseId;
        $this->editSets = $display['sets'];
        $this->editReps = $display['reps'];
        $this->editRest = $display['rest_seconds'];
    }

    public function saveExerciseEdit(AetherWorkoutLogService $logs): void
    {
        if ($this->editExerciseId === null) {
            return;
        }

        $exercise = $this->findProgramExercise($this->editExerciseId);
        $logs->updateExercisePrescription(
            Auth::user(),
            $this->program,
            $exercise,
            max(1, min(8, $this->editSets)),
            $this->editReps,
            max(30, min(300, $this->editRest)),
        );

        $this->editExerciseId = null;
        $this->program->load('workoutDays.exercises.prescriptionSets');
    }

    public function render(
        UserAetherProgramHistoryService $historyService,
        AetherWorkoutLogService $workoutLogs,
        ExerciseMediaResolver $mediaResolver,
        ExerciseSubstitutionService $substitutions,
        AetherWorkoutSessionService $sessions,
        ExerciseSetPrescriptionBuilder $setBuilder,
    ): View {
        $locale = LocaleConfig::fromRoute();
        $user = Auth::user();

        $record = $historyService->recordsForUser($user, $locale)
            ->firstWhere('uuid', $this->program->uuid);

        $setLogs = $workoutLogs->logsForProgram($user, $this->program);
        $completedSetIds = $setLogs
            ->where('completed', true)
            ->mapWithKeys(fn (AetherWorkoutSetLog $log): array => [
                $log->aether_program_exercise_set_id => true,
            ])
            ->all();

        $selectedDay = $this->selectedWorkoutDay();
        $activeSession = $selectedDay
            ? $sessions->activeForDay($user, $this->program, $selectedDay)
            : null;

        $swapSuggestions = collect();
        $swapExercise = null;

        if ($this->swapExerciseId !== null && $this->program->profile !== null) {
            $swapExercise = $this->findProgramExercise($this->swapExerciseId);
            $swapSuggestions = $this->swapSearchQuery !== ''
                ? $substitutions->searchForProfile($this->program->profile, $this->swapSearchQuery)
                : $substitutions->suggestionsFor($swapExercise, $this->program->profile);
        }

        $exerciseMedia = $this->resolveExerciseMedia($selectedDay, $mediaResolver);

        return view('livewire.profile.program-show', [
            'locale' => $locale,
            'record' => $record,
            'metabolic' => $this->program->metabolicSummary(),
            'coachNotes' => $this->program->coachNarrative()->toDisplayMap(),
            'shoppingList' => $this->program->shopping_list_summary,
            'split' => $this->program->split?->value,
            'completionPercent' => $workoutLogs->weekCompletionPercent($user, $this->program),
            'selectedDay' => $selectedDay,
            'activeSession' => $activeSession,
            'completedSetIds' => $completedSetIds,
            'displayExercises' => $selectedDay
                ? $selectedDay->exercises->mapWithKeys(fn (AetherProgramExercise $exercise): array => [
                    $exercise->id => $workoutLogs->displayExercise($exercise, $user),
                ])
                : collect(),
            'exerciseMedia' => $exerciseMedia,
            'swapExercise' => $swapExercise,
            'swapSuggestions' => $swapSuggestions,
            'totalWorkoutDays' => $this->program->workoutDays->count(),
            'completedWorkoutDays' => $this->countCompletedWorkoutDays($setLogs),
            'setBuilder' => $setBuilder,
        ]);
    }

    private function selectedWorkoutDay()
    {
        return $this->program->workoutDays->firstWhere('id', $this->selectedWorkoutDayId);
    }

    private function findProgramExercise(int $exerciseId): AetherProgramExercise
    {
        $exercise = $this->program->workoutDays
            ->flatMap(fn ($day) => $day->exercises)
            ->firstWhere('id', $exerciseId);

        abort_if($exercise === null, 404);

        return $exercise;
    }

    private function findProgramExerciseSet(int $exerciseSetId): AetherProgramExerciseSet
    {
        $set = $this->program->workoutDays
            ->flatMap(fn ($day) => $day->exercises)
            ->flatMap(fn ($exercise) => $exercise->prescriptionSets)
            ->firstWhere('id', $exerciseSetId);

        abort_if($set === null, 404);

        return $set;
    }

    /**
     * @return array<int, array{gif_url: ?string, video_url: ?string, image_url: ?string}>
     */
    private function resolveExerciseMedia($selectedDay, ExerciseMediaResolver $mediaResolver): array
    {
        if ($selectedDay === null) {
            return [];
        }

        $media = [];

        foreach ($selectedDay->exercises as $exercise) {
            $display = app(AetherWorkoutLogService::class)->displayExercise($exercise, Auth::user());
            $resolved = $mediaResolver->resolveBySlug($display['slug']);
            $media[$exercise->id] = $resolved ?? [
                'gif_url' => null,
                'video_url' => null,
                'image_url' => null,
            ];
        }

        return $media;
    }

    private function countCompletedWorkoutDays(Collection $setLogs): int
    {
        return $this->program->workoutDays
            ->filter(function ($day) use ($setLogs): bool {
                $setIds = $day->exercises
                    ->flatMap(fn ($exercise) => $exercise->prescriptionSets)
                    ->pluck('id');

                if ($setIds->isEmpty()) {
                    return false;
                }

                $completed = $setLogs
                    ->whereIn('aether_program_exercise_set_id', $setIds->all())
                    ->where('completed', true)
                    ->count();

                return $completed >= $setIds->count();
            })
            ->count();
    }
}
