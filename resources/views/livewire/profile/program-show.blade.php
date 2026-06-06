<div class="eg-profile-page eg-profile-program-detail">
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

    <section class="container container-xl eg-profile-section">
        <div class="eg-profile-program-hero eg-glass mb-4">
            <span class="eg-badge mb-3">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                {{ $record['target_label'] ?? __('profile.programs_title') }}
            </span>
            <h1 class="eg-display h3 mb-2">{{ $record['title'] ?? __('profile.programs_title') }}</h1>
            <p class="eg-text-muted mb-3">{{ $record['summary'] ?? '' }}</p>
            <div class="eg-profile-program-meta small eg-text-muted">
                <span>{{ __('profile.program_created', ['date' => $record['created_at_label'] ?? '']) }}</span>
                @if (! empty($record['mission_title']))
                    <span class="mx-2" aria-hidden="true">·</span>
                    <span>{{ __('profile.program_mission', ['mission' => $record['mission_title']]) }}</span>
                @endif
                <span class="mx-2" aria-hidden="true">·</span>
                <span>{{ __('profile.program_status', ['status' => ucfirst($record['status'] ?? $program->status->value)]) }}</span>
            </div>
        </div>

        @if (! empty(array_filter($metabolic)))
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-3">{{ __('profile.program_metabolic_title') }}</h2>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <p class="small eg-text-muted mb-1">{{ __('profile.program_calories') }}</p>
                        <p class="fw-semibold mb-0">{{ eg_num($metabolic['target_calories'] ?? 0) }} {{ __('missions.kcal') }}</p>
                    </div>
                    <div class="col-md-3 col-6">
                        <p class="small eg-text-muted mb-1">{{ __('profile.program_protein') }}</p>
                        <p class="fw-semibold mb-0">{{ eg_num($metabolic['protein_grams'] ?? 0) }} g</p>
                    </div>
                    <div class="col-md-3 col-6">
                        <p class="small eg-text-muted mb-1">{{ __('profile.program_carbs') }}</p>
                        <p class="fw-semibold mb-0">{{ eg_num($metabolic['carb_grams'] ?? 0) }} g</p>
                    </div>
                    <div class="col-md-3 col-6">
                        <p class="small eg-text-muted mb-1">{{ __('profile.program_fat') }}</p>
                        <p class="fw-semibold mb-0">{{ eg_num($metabolic['fat_grams'] ?? 0) }} g</p>
                    </div>
                </div>
            </div>
        @endif

        @if (! empty($coachNotes))
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-3">{{ __('profile.program_narrative_title') }}</h2>
                @foreach ($coachNotes as $key => $text)
                    <div class="mb-3">
                        <p class="small fw-semibold text-capitalize mb-1">{{ str_replace('_', ' ', (string) $key) }}</p>
                        <p class="mb-0">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($program->workoutDays->isNotEmpty())
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-1">{{ __('profile.program_workout_title') }}</h2>
                @if ($split)
                    <p class="small eg-text-muted mb-3">{{ __('profile.program_split', ['split' => $split]) }}</p>
                @endif
                @foreach ($program->workoutDays as $day)
                    <article class="eg-profile-program-day-card mb-3">
                        <header class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <strong>{{ $day->label }}</strong>
                            @if ($day->focus)
                                <span class="eg-badge">{{ $day->focus }}</span>
                            @endif
                        </header>
                        @if ($day->motivation)
                            <p class="small eg-text-muted">{{ $day->motivation }}</p>
                        @endif
                        <ul class="mb-0 small">
                            @foreach ($day->exercises as $exercise)
                                <li class="mb-1">
                                    <span class="fw-semibold">{{ $exercise->name }}</span>
                                    — {{ eg_num($exercise->sets) }}×{{ $exercise->reps }}
                                    @if ($exercise->notes)
                                        <span class="eg-text-muted">({{ $exercise->notes }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($program->nutritionDays->isNotEmpty())
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-3">{{ __('profile.program_nutrition_title') }}</h2>
                @foreach ($program->nutritionDays as $day)
                    <article class="eg-profile-program-day-card mb-3">
                        <header class="mb-2">
                            <strong>{{ __('profile.program_day', ['day' => $day->day_index]) }}</strong>
                            <span class="small eg-text-muted ms-2">
                                {{ eg_num($day->total_calories) }} {{ __('missions.kcal') }}
                            </span>
                        </header>
                        @foreach ($day->meals as $meal)
                            <div class="mb-2">
                                <p class="small fw-semibold mb-1">
                                    {{ ucfirst($meal->meal_type->value) }} — {{ $meal->name }}
                                    <span class="eg-text-muted">({{ eg_num($meal->calories) }} {{ __('missions.kcal') }})</span>
                                </p>
                                @if ($meal->ingredients->isNotEmpty())
                                    <ul class="small eg-text-muted mb-0">
                                        @foreach ($meal->ingredients as $ingredient)
                                            <li>{{ $ingredient->ingredient }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($meal->instructions)
                                    <p class="small mb-0">{{ $meal->instructions }}</p>
                                @endif
                            </div>
                        @endforeach
                    </article>
                @endforeach
            </div>
        @endif

        @if (is_string($shoppingList) && $shoppingList !== '')
            <div class="eg-profile-program-block eg-glass mb-4">
                <h2 class="h5 mb-3">{{ __('missions.ai_meal_shopping') }}</h2>
                <p class="mb-0">{{ $shoppingList }}</p>
            </div>
        @endif
    </section>
</div>
