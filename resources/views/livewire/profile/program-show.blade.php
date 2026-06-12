<div class="eg-profile-page eg-profile-program-detail eg-aether-program-page">
    <section class="container container-xl pt-4">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('profile').'#my-programs',
                    'label' => __('profile.programs_back'),
                    'icon' => 'fa-arrow-left',
                    'directional' => true,
                    'wireNavigate' => true,
                ],
                [
                    'href' => route('profile'),
                    'label' => __('profile.back_to_profile'),
                    'icon' => 'fa-user',
                    'wireNavigate' => true,
                ],
            ],
        ])
    </section>

    <section class="container container-xl eg-profile-section pb-5">
        <div class="eg-aether-program-hero mb-4">
            <div class="eg-aether-program-hero__orb eg-aether-program-hero__orb--emerald" aria-hidden="true"></div>
            <div class="eg-aether-program-hero__orb eg-aether-program-hero__orb--indigo" aria-hidden="true"></div>

            <span class="eg-badge mb-3">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                {{ $record['target_label'] ?? __('profile.programs_title') }}
            </span>
            <h1 class="eg-display h3 mb-2">{{ $program->coach_title ?: ($record['title'] ?? __('profile.programs_title')) }}</h1>
            @if ($program->coach_week_focus)
                <p class="mb-2">{{ $program->coach_week_focus }}</p>
            @endif
            <p class="eg-text-muted mb-3">{{ $record['summary'] ?? '' }}</p>
            <div class="eg-profile-program-meta small eg-text-muted">
                <span>{{ __('profile.program_week_label', ['week' => eg_num($program->week_number), 'total' => eg_num(12)]) }}</span>
                <span class="mx-2" aria-hidden="true">·</span>
                <span>{{ __('profile.program_created', ['date' => $record['created_at_label'] ?? '']) }}</span>
                @if (! empty($record['mission_title']))
                    <span class="mx-2" aria-hidden="true">·</span>
                    <span>{{ __('profile.program_mission', ['mission' => $record['mission_title']]) }}</span>
                @endif
            </div>
        </div>

        <div class="eg-aether-section-tabs" role="tablist">
            @foreach (['overview' => __('profile.program_tab_overview'), 'workout' => __('profile.program_tab_workout'), 'nutrition' => __('profile.program_tab_nutrition'), 'coach' => __('profile.program_tab_coach')] as $key => $label)
                <button type="button" role="tab" @class(['eg-aether-section-tab', 'is-active' => $activeSection === $key]) wire:click="setProgramSection('{{ $key }}')">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($activeSection === 'overview')
            <div class="eg-profile-program-block eg-glass mb-4">
                <div class="eg-aether-overview-stats">
                    <div class="eg-aether-overview-stat">
                        <span class="small eg-text-muted">{{ __('profile.program_week_sessions') }}</span>
                        <strong>{{ eg_num($totalWorkoutDays) }}</strong>
                    </div>
                    <div class="eg-aether-overview-stat">
                        <span class="small eg-text-muted">{{ __('profile.program_week_completed') }}</span>
                        <strong>{{ eg_num($completedWorkoutDays) }}</strong>
                    </div>
                    <div class="eg-aether-overview-stat">
                        <span class="small eg-text-muted">{{ __('profile.program_week_progress') }}</span>
                        <strong>{{ eg_num($completionPercent) }}%</strong>
                    </div>
                </div>
                <div class="eg-aether-progress-bar mt-3" aria-hidden="true">
                    <span style="width: {{ $completionPercent }}%"></span>
                </div>
            </div>
        @endif

        @if ($activeSection === 'overview' && ! empty(array_filter($metabolic)))
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-3">{{ __('profile.program_metabolic_title') }}</h2>
                <div class="eg-aether-macro-grid">
                    <div class="eg-aether-macro-card">
                        <span class="small eg-text-muted">{{ __('profile.program_calories') }}</span>
                        <strong>{{ eg_num($metabolic['target_calories'] ?? 0) }}</strong>
                        <span class="small">{{ __('missions.kcal') }}</span>
                    </div>
                    <div class="eg-aether-macro-card">
                        <span class="small eg-text-muted">{{ __('profile.program_protein') }}</span>
                        <strong>{{ eg_num($metabolic['protein_grams'] ?? 0) }} g</strong>
                    </div>
                    <div class="eg-aether-macro-card">
                        <span class="small eg-text-muted">{{ __('profile.program_carbs') }}</span>
                        <strong>{{ eg_num($metabolic['carb_grams'] ?? 0) }} g</strong>
                    </div>
                    <div class="eg-aether-macro-card">
                        <span class="small eg-text-muted">{{ __('profile.program_fat') }}</span>
                        <strong>{{ eg_num($metabolic['fat_grams'] ?? 0) }} g</strong>
                    </div>
                </div>
                @if ($split)
                    <p class="small eg-text-muted mt-3 mb-0">{{ __('profile.program_split', ['split' => $split]) }}</p>
                @endif
            </div>
        @endif

        @if ($activeSection === 'coach' && ! empty($coachNotes))
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-3">{{ __('profile.program_narrative_title') }}</h2>
                @foreach ($coachNotes as $key => $text)
                    <div class="eg-aether-coach-card">
                        <p class="small fw-semibold text-capitalize mb-1">{{ str_replace('_', ' ', (string) $key) }}</p>
                        <p class="mb-0">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($activeSection === 'workout' && $program->workoutDays->isNotEmpty())
            <div class="eg-aether-day-tabs mb-4" role="tablist">
                @foreach ($program->workoutDays as $day)
                    <button type="button" role="tab" @class(['eg-aether-day-tab', 'is-active' => $selectedWorkoutDayId === $day->id]) wire:click="selectWorkoutDay({{ $day->id }})">
                        <span class="eg-aether-day-tab__index">{{ __('profile.program_day_short', ['day' => $day->day_index]) }}</span>
                        <span class="eg-aether-day-tab__label">{{ $day->label }}</span>
                    </button>
                @endforeach
            </div>

            @if ($selectedDay)
                <div
                    class="eg-aether-gym-session"
                    x-data="{
                        restSeconds: 0,
                        restTotal: 90,
                        resting: false,
                        interval: null,
                        startRest(seconds) {
                            this.restTotal = seconds;
                            this.restSeconds = seconds;
                            this.resting = true;
                            clearInterval(this.interval);
                            this.interval = setInterval(() => {
                                if (this.restSeconds <= 0) {
                                    this.skipRest();
                                    return;
                                }
                                this.restSeconds--;
                            }, 1000);
                        },
                        skipRest() {
                            clearInterval(this.interval);
                            this.resting = false;
                            this.restSeconds = 0;
                        }
                    }"
                    x-on:aether-rest-timer.window="startRest($event.detail.seconds)"
                >
                    <header class="eg-aether-gym-session__head mb-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <h2 class="h5 mb-1">{{ $selectedDay->label }}</h2>
                            @if ($selectedDay->focus)
                                <p class="small eg-text-muted mb-0">{{ $selectedDay->focus }}</p>
                            @endif
                            @if ($selectedDay->motivation)
                                <p class="small mb-0 mt-1">{{ $selectedDay->motivation }}</p>
                            @endif
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" wire:click="startWorkoutSession">
                            <i class="fa-solid fa-play me-1"></i>{{ $activeSession ? __('profile.program_session_resume') : __('profile.program_session_start') }}
                        </button>
                    </header>

                    <div class="eg-aether-rest-timer" x-show="resting" x-cloak>
                        <div class="eg-aether-rest-timer__ring" :style="`--progress: ${restTotal ? (restSeconds / restTotal) : 0}`">
                            <span x-text="restSeconds"></span>
                        </div>
                        <p class="mb-2">{{ __('profile.program_rest_message') }}</p>
                        <button type="button" class="btn btn-sm btn-outline-light" x-on:click="skipRest()">{{ __('profile.program_skip_rest') }}</button>
                    </div>

                    <div class="eg-aether-exercise-stack">
                        @foreach ($selectedDay->exercises as $exercise)
                            @php
                                $display = $displayExercises[$exercise->id] ?? null;
                                $media = $exerciseMedia[$exercise->id] ?? null;
                                $targetReps = (int) preg_replace('/\D/', '', (string) ($display['reps'] ?? $exercise->reps)) ?: 10;
                            @endphp
                            <article class="eg-aether-exercise-session" wire:key="exercise-{{ $exercise->id }}">
                                <div class="eg-aether-exercise-session__media">
                                    @if (! empty($media['gif_url']))
                                        <img src="{{ $media['gif_url'] }}" alt="{{ $display['name'] ?? $exercise->name }}" class="eg-aether-exercise-gif" loading="lazy">
                                    @elseif (! empty($media['image_url']))
                                        <img src="{{ $media['image_url'] }}" alt="{{ $display['name'] ?? $exercise->name }}" class="eg-aether-exercise-gif" loading="lazy">
                                    @else
                                        <div class="eg-aether-exercise-gif eg-aether-exercise-gif--placeholder">
                                            <i class="fa-solid fa-dumbbell"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="eg-aether-exercise-session__body">
                                    <header class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <h3 class="h6 mb-0">{{ $display['name'] ?? $exercise->name }}</h3>
                                            <span class="small eg-text-muted">{{ eg_num($display['sets'] ?? $exercise->sets) }}×{{ $display['reps'] ?? $exercise->reps }}</span>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-light" wire:click="openExerciseEdit({{ $exercise->id }})" title="{{ __('profile.program_edit_exercise') }}">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-light" wire:click="openSwapModal({{ $exercise->id }})" title="{{ __('profile.program_swap_exercise') }}">
                                                <i class="fa-solid fa-shuffle"></i>
                                            </button>
                                        </div>
                                    </header>

                                    @if ($editExerciseId === $exercise->id)
                                        <div class="eg-aether-exercise-edit mb-3">
                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <label class="small">{{ __('profile.program_sets_col') }}</label>
                                                    <input type="number" min="1" max="8" class="form-control form-control-sm" wire:model.live="editSets">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small">{{ __('profile.program_reps_col') }}</label>
                                                    <input type="text" class="form-control form-control-sm" wire:model.live="editReps">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small">{{ __('profile.program_rest_col') }}</label>
                                                    <input type="number" min="30" max="300" class="form-control form-control-sm" wire:model.live="editRest">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary mt-2" wire:click="saveExerciseEdit">{{ __('profile.program_save_exercise') }}</button>
                                        </div>
                                    @endif

                                    <div class="eg-aether-set-buttons">
                                        @foreach ($exercise->prescriptionSets as $prescriptionSet)
                                            @php
                                                $repsLabel = $setBuilder->displayReps($prescriptionSet->target_reps_min, $prescriptionSet->target_reps_max);
                                            @endphp
                                            <button
                                                type="button"
                                                @class(['eg-aether-set-btn', 'is-done' => ! empty($completedSetIds[$prescriptionSet->id])])
                                                wire:click="toggleWorkoutSet({{ $prescriptionSet->id }})"
                                            >
                                                <span>{{ __('profile.program_set_label', ['set' => $prescriptionSet->set_number]) }}</span>
                                                <span class="small">{{ $repsLabel }} {{ __('profile.program_reps_short') }}</span>
                                                @if (! empty($completedSetIds[$prescriptionSet->id]))
                                                    <i class="fa-solid fa-check"></i>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($swapExerciseId !== null && $swapExercise)
                <div class="eg-aether-swap-modal" role="dialog" aria-modal="true">
                    <div class="eg-aether-swap-modal__backdrop" wire:click="closeSwapModal"></div>
                    <div class="eg-aether-swap-modal__panel eg-glass">
                        <header class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 mb-0">{{ __('profile.program_swap_title', ['name' => $swapExercise->name]) }}</h3>
                            <button type="button" class="btn btn-sm btn-outline-light" wire:click="closeSwapModal"><i class="fa-solid fa-xmark"></i></button>
                        </header>
                        <input type="search" class="form-control mb-3" wire:model.live.debounce.300ms="swapSearchQuery" placeholder="{{ __('profile.program_swap_search') }}">
                        <div class="eg-aether-swap-list">
                            @forelse ($swapSuggestions as $candidate)
                                <button
                                    type="button"
                                    class="eg-aether-swap-item"
                                    wire:click="selectSwapCandidate({{ $swapExercise->id }}, '{{ $candidate->slug }}')"
                                >
                                    <strong>{{ $candidate->name }}</strong>
                                    <span class="small eg-text-muted">{{ $candidate->muscle_group->value }}</span>
                                </button>
                            @empty
                                <p class="small eg-text-muted mb-0">{{ __('profile.program_swap_empty') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        @endif

        @if ($activeSection === 'nutrition' && $program->nutritionDays->isNotEmpty())
            @foreach ($program->nutritionDays as $day)
                <div class="eg-profile-program-block eg-glass mb-3">
                    <header class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0">{{ __('profile.program_day', ['day' => $day->day_index]) }}</h3>
                        <span class="eg-badge">{{ eg_num($day->total_calories) }} {{ __('missions.kcal') }}</span>
                    </header>
                    <div class="eg-aether-meal-grid">
                        @foreach ($day->meals as $meal)
                            <div class="eg-aether-meal-card">
                                <p class="fw-semibold mb-1">
                                    {{ ucfirst($meal->meal_type->value) }} — {{ $meal->name }}
                                    <span class="eg-text-muted small">({{ eg_num($meal->calories) }} {{ __('missions.kcal') }})</span>
                                </p>
                                @if ($meal->ingredients->isNotEmpty())
                                    <div class="eg-aether-shopping-chips mb-2">
                                        @foreach ($meal->ingredients as $ingredient)
                                            <span class="eg-aether-shopping-chip">{{ $ingredient->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($meal->instructions)
                                    <p class="small eg-text-muted mb-0">{{ $meal->instructions }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if (is_string($shoppingList) && $shoppingList !== '')
                <div class="eg-profile-program-block eg-glass mb-4">
                    <h2 class="h6 mb-3">{{ __('missions.ai_meal_shopping') }}</h2>
                    <div class="eg-aether-shopping-chips">
                        @foreach (explode(',', $shoppingList) as $item)
                            @if (trim($item) !== '')
                                <span class="eg-aether-shopping-chip">{{ trim($item) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        @if ($activeSection === 'overview')
            @if ($program->workoutDays->isNotEmpty())
                <div class="eg-profile-program-block eg-glass mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">{{ __('profile.program_workout_title') }}</h2>
                        <button type="button" class="btn btn-sm btn-outline-light" wire:click="setProgramSection('workout')">{{ __('profile.program_tab_workout') }}</button>
                    </div>
                    <p class="eg-text-muted small mb-0">{{ __('profile.program_summary_workout', ['days' => $program->workoutDays->count(), 'split' => $split ?? '—']) }}</p>
                </div>
            @endif
            @if ($program->nutritionDays->isNotEmpty())
                <div class="eg-profile-program-block eg-glass mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">{{ __('profile.program_nutrition_title') }}</h2>
                        <button type="button" class="btn btn-sm btn-outline-light" wire:click="setProgramSection('nutrition')">{{ __('profile.program_tab_nutrition') }}</button>
                    </div>
                    <p class="eg-text-muted small mb-0">{{ __('missions.ai_meal_summary_macros', ['calories' => $metabolic['target_calories'] ?? 0, 'protein' => $metabolic['protein_grams'] ?? 0]) }}</p>
                </div>
            @endif
        @endif
    </section>
</div>
