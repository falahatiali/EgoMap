<div class="eg-mission-program">
    <header class="eg-mission-program__head">
        <div class="eg-mission-program__intro">
            <span class="eg-mission-program__eyebrow">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                {{ __('missions.tab_program') }}
            </span>
            <h2 class="eg-mission-program__title">{{ __('missions.tab_program') }}</h2>
            <p class="eg-mission-program__lead">{{ __('missions.tab_program_help') }}</p>
        </div>
        @if ($enrollmentProgress > 0)
            <div class="eg-mission-program__progress" aria-label="{{ __('missions.mission_progress') }}">
                <div class="eg-mission-program__progress-copy">
                    <span class="eg-mission-program__progress-label">{{ __('missions.mission_progress') }}</span>
                    <strong class="eg-mission-program__progress-value">{{ eg_num($enrollmentProgress) }}%</strong>
                </div>
                <div class="eg-aether-progress-bar" aria-hidden="true">
                    <span style="width: {{ $enrollmentProgress }}%"></span>
                </div>
            </div>
        @endif
    </header>

    <div class="eg-aether-plans">
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
                <article class="eg-aether-plan-card eg-aether-plan-card--workout">
                    <div class="eg-aether-plan-card__orb eg-aether-plan-card__orb--emerald" aria-hidden="true"></div>
                    <div class="eg-aether-plan-card__content">
                        <div class="eg-aether-plan-card__icon" aria-hidden="true">
                            <i class="fa-solid fa-dumbbell"></i>
                        </div>
                        <div class="eg-aether-plan-card__copy">
                            <h3 class="eg-aether-plan-card__title">{{ __('missions.ai_workout') }}</h3>
                            <p class="eg-aether-plan-card__text">
                                {{ $canAiWorkout ? __('missions.ai_workout_pro_hint') : __('missions.pro_hint') }}
                            </p>
                        </div>
                        <div class="eg-aether-plan-card__action">
                            @if ($canAiWorkout)
                                <button
                                    type="button"
                                    class="eg-aether-plan-card__cta"
                                    wire:click="openAiWorkoutGenerator"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="openAiWorkoutGenerator">
                                        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                        {{ __('missions.ai_generate_workout_cta') }}
                                    </span>
                                    <span wire:loading wire:target="openAiWorkoutGenerator">{{ __('missions.ai_generating') }}</span>
                                </button>
                            @else
                                <a href="{{ route('pricing', ['locale' => app()->getLocale()]) }}" class="eg-aether-plan-card__cta eg-aether-plan-card__cta--pro" wire:navigate>
                                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                                    {{ __('missions.pro_upgrade_cta') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
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
                <article class="eg-aether-plan-card eg-aether-plan-card--meal">
                    <div class="eg-aether-plan-card__orb eg-aether-plan-card__orb--indigo" aria-hidden="true"></div>
                    <div class="eg-aether-plan-card__content">
                        <div class="eg-aether-plan-card__icon" aria-hidden="true">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <div class="eg-aether-plan-card__copy">
                            <h3 class="eg-aether-plan-card__title">{{ __('missions.ai_meal') }}</h3>
                            <p class="eg-aether-plan-card__text">
                                {{ $canAiMeal ? __('missions.ai_meal_pro_hint') : __('missions.pro_hint') }}
                            </p>
                        </div>
                        <div class="eg-aether-plan-card__action">
                            @if ($canAiMeal)
                                <button
                                    type="button"
                                    class="eg-aether-plan-card__cta"
                                    wire:click="openAiMealGenerator"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="openAiMealGenerator">
                                        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                        {{ __('missions.ai_generate_meal_cta') }}
                                    </span>
                                    <span wire:loading wire:target="openAiMealGenerator">{{ __('missions.ai_generating') }}</span>
                                </button>
                            @else
                                <a href="{{ route('pricing', ['locale' => app()->getLocale()]) }}" class="eg-aether-plan-card__cta eg-aether-plan-card__cta--pro" wire:navigate>
                                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                                    {{ __('missions.pro_upgrade_cta') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endif
        @endif
    </div>

    @if (! $requiresProWorkout && ! $requiresProMeal)
        <div class="eg-mission-program__empty">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <p>{{ __('missions.tab_program_empty') }}</p>
        </div>
    @endif
</div>
