<div class="eg-mission-block mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="eg-mission-block-title mb-1">{{ __('missions.tab_program') }}</h2>
            <p class="eg-text-muted small mb-0">{{ __('missions.tab_program_help') }}</p>
        </div>
        @if ($enrollmentProgress > 0)
            <div class="text-end">
                <span class="small eg-text-muted">{{ __('missions.mission_progress') }}</span>
                <div class="fw-bold" style="color:#6ee7b7;">{{ eg_num($enrollmentProgress) }}%</div>
            </div>
        @endif
    </div>

    @if ($enrollmentProgress > 0)
        <div class="eg-aether-progress-bar mb-4" aria-hidden="true">
            <span style="width: {{ $enrollmentProgress }}%"></span>
        </div>
    @endif
</div>

@if ($requiresProWorkout)
    @if ($activeWorkoutProgram)
        @include('livewire.missions.partials.workspace-ai-program-card', [
            'target' => 'workout',
            'program' => $activeWorkoutProgram,
            'profileUrl' => $programHistoryUrl,
            'locale' => $locale,
            'summary' => $activeWorkoutProgramSummary,
            'adherencePercent' => $workoutAdherencePercent,
        ])
    @else
        <div class="eg-mission-pro-banner d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <strong>{{ __('missions.ai_workout') }}</strong>
                <p class="small mb-0 eg-text-muted">
                    {{ $canAiWorkout ? __('missions.ai_workout_pro_hint') : __('missions.pro_hint') }}
                </p>
            </div>
            @if ($canAiWorkout)
                <button type="button" class="btn btn-sm btn-primary" wire:click="openAiWorkoutGenerator" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="openAiWorkoutGenerator">{{ __('missions.ai_generate_workout_cta') }}</span>
                    <span wire:loading wire:target="openAiWorkoutGenerator">{{ __('missions.ai_generating') }}</span>
                </button>
            @else
                <a href="{{ route('pricing', ['locale' => app()->getLocale()]) }}" class="btn btn-sm btn-warning" wire:navigate>
                    {{ __('missions.pro_upgrade_cta') }}
                </a>
            @endif
        </div>
    @endif
@endif

@if ($requiresProMeal)
    @if ($activeMealProgram)
        @include('livewire.missions.partials.workspace-ai-program-card', [
            'target' => 'meal',
            'program' => $activeMealProgram,
            'profileUrl' => $programHistoryUrl,
            'locale' => $locale,
            'summary' => $activeMealProgramSummary,
            'adherencePercent' => null,
        ])
    @else
        <div class="eg-mission-pro-banner d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <strong>{{ __('missions.ai_meal') }}</strong>
                <p class="small mb-0 eg-text-muted">
                    {{ $canAiMeal ? __('missions.ai_meal_pro_hint') : __('missions.pro_hint') }}
                </p>
            </div>
            @if ($canAiMeal)
                <button type="button" class="btn btn-sm btn-primary" wire:click="openAiMealGenerator" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="openAiMealGenerator">{{ __('missions.ai_generate_meal_cta') }}</span>
                    <span wire:loading wire:target="openAiMealGenerator">{{ __('missions.ai_generating') }}</span>
                </button>
            @else
                <a href="{{ route('pricing', ['locale' => app()->getLocale()]) }}" class="btn btn-sm btn-warning" wire:navigate>
                    {{ __('missions.pro_upgrade_cta') }}
                </a>
            @endif
        </div>
    @endif
@endif

@if (! $requiresProWorkout && ! $requiresProMeal)
    <p class="eg-text-muted small mb-0">{{ __('missions.tab_program_empty') }}</p>
@endif
