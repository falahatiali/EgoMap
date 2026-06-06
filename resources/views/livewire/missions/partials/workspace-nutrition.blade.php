@if ($requiresProMeal)
    @if ($hasUserMealProgram)
        @include('livewire.missions.partials.workspace-ai-program-card', [
            'target' => 'meal',
            'program' => $activeMealProgram,
            'profileUrl' => $programHistoryUrl,
            'locale' => $locale,
            'summary' => $activeMealProgramSummary,
        ])
    @else
        <div class="eg-mission-pro-banner d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <strong>{{ __('missions.ai_meal') }}</strong>
                <p class="small mb-0 eg-text-muted">
                    {{ $canAiMeal ? __('missions.ai_meal_pro_hint') : __('missions.nutrition_ai_hint') }}
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

@if ($mealPlanNotes !== '' || $activeMealProgram !== null)
    <div class="eg-mission-block mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="eg-mission-block-title mb-1">{{ __('missions.ai_meal_plan_title') }}</h2>
                <p class="eg-text-muted small mb-0">{{ __('missions.ai_meal_plan_help') }}</p>
            </div>
            @if ($activeMealProgram)
                <a href="{{ route('profile.program.show', ['uuid' => $activeMealProgram->uuid]) }}" class="btn btn-sm btn-outline-light" wire:navigate>
                    {{ __('missions.ai_program_view_full') }}
                </a>
            @endif
        </div>

        @if ($mealPlanNotes !== '')
            <div class="eg-mission-program-notes">{!! nl2br(e($mealPlanNotes)) !!}</div>
        @endif

        @if ($activeMealProgram)
            @if ($activeMealProgram->metabolic_target_calories)
                <p class="small fw-semibold mt-3 mb-2">
                    {{ __('missions.ai_meal_summary_macros', [
                        'calories' => $activeMealProgram->metabolic_target_calories,
                        'protein' => $activeMealProgram->metabolic_protein_grams ?? 0,
                    ]) }}
                </p>
            @endif
            @foreach ($activeMealProgram->nutritionDays->take(3) as $day)
                <div class="eg-mission-meal-card mb-2">
                    <p class="small fw-semibold mb-1">{{ __('missions.ai_meal_day_line', [
                        'day' => $day->day_index,
                        'meals' => $day->meals->map(fn ($meal) => $meal->name.' ('.$meal->calories.' '.__('missions.kcal').')')->implode(' · '),
                    ]) }}</p>
                </div>
            @endforeach
            @if ($activeMealProgram->nutritionDays->count() > 3)
                <p class="small eg-text-muted mb-0">{{ __('missions.ai_program_more_days', ['count' => $activeMealProgram->nutritionDays->count() - 3]) }}</p>
            @endif
        @endif
    </div>
@endif

<div class="eg-mission-block">
    <h2 class="eg-mission-block-title">{{ __('missions.nutrition_day_log') }}</h2>
    <p class="eg-text-muted small mb-3">{{ __('missions.nutrition_day_log_help') }}</p>

    <form wire:submit="saveNutritionDay">
        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <label class="form-label">{{ __('missions.nutrition_day_notes') }}</label>
                <textarea class="form-control" rows="2" wire:model="nutritionDayNotes" placeholder="{{ __('missions.nutrition_quality_placeholder') }}"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.meal_quality') }}</label>
                <select class="form-select" wire:model="nutritionQuality">
                    <option value="">{{ __('missions.not_set') }}</option>
                    @for ($i = 1; $i <= 10; $i++)
                        <option value="{{ $i }}">{{ eg_num($i) }}/{{ eg_num(10) }}</option>
                    @endfor
                </select>
            </div>
        </div>

        @foreach ($nutritionMeals as $mealIndex => $meal)
            <div class="eg-mission-meal-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h6 mb-0">{{ __('missions.meal_number', ['n' => eg_num($mealIndex + 1)]) }}</h3>
                    @if (count($nutritionMeals) > 1)
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeNutritionMeal({{ $mealIndex }})">{{ __('missions.workout_remove') }}</button>
                    @endif
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('missions.meal_type') }}</label>
                        <select class="form-select" wire:model="nutritionMeals.{{ $mealIndex }}.meal_type">
                            @foreach ($mealTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label($locale) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('missions.meal_time') }}</label>
                        <input type="time" class="form-control" wire:model="nutritionMeals.{{ $mealIndex }}.meal_time">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">{{ __('missions.meal_notes') }}</label>
                        <input type="text" class="form-control" wire:model="nutritionMeals.{{ $mealIndex }}.notes">
                    </div>
                </div>

                <div class="mb-2">
                    <p class="small fw-semibold mb-1">{{ __('missions.meal_items') }}</p>
                    <p class="small eg-text-muted mb-0">{{ __('missions.nutrition_items_examples') }}</p>
                </div>
                @foreach ($meal['items'] as $itemIndex => $item)
                    <div class="row g-2 mb-2 align-items-end">
                        <div class="col-md-5">
                            @if ($itemIndex === 0)
                                <label class="form-label mb-1">{{ __('missions.food_name') }}</label>
                            @endif
                            <input type="text" class="form-control" wire:model="nutritionMeals.{{ $mealIndex }}.items.{{ $itemIndex }}.name" placeholder="{{ __('missions.food_name_placeholder') }}">
                        </div>
                        <div class="col-md-3">
                            @if ($itemIndex === 0)
                                @include('livewire.missions.partials.form-label-tooltip', [
                                    'label' => __('missions.qty'),
                                    'help' => __('missions.nutrition_qty_help'),
                                ])
                            @endif
                            <input type="number" step="0.1" min="0" class="form-control" wire:model="nutritionMeals.{{ $mealIndex }}.items.{{ $itemIndex }}.quantity" placeholder="{{ __('missions.nutrition_qty_placeholder') }}">
                        </div>
                        <div class="col-md-3">
                            @if ($itemIndex === 0)
                                @include('livewire.missions.partials.form-label-tooltip', [
                                    'label' => __('missions.unit'),
                                    'help' => __('missions.nutrition_unit_help'),
                                ])
                            @endif
                            <input type="text" class="form-control" wire:model="nutritionMeals.{{ $mealIndex }}.items.{{ $itemIndex }}.unit" placeholder="{{ __('missions.nutrition_unit_placeholder') }}">
                        </div>
                        <div class="col-md-1">
                            @if (count($meal['items']) > 1)
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeMealItem({{ $mealIndex }}, {{ $itemIndex }})" aria-label="{{ __('missions.workout_remove') }}">×</button>
                            @endif
                        </div>
                    </div>
                @endforeach
                <button type="button" class="btn btn-sm btn-outline-light mb-2" wire:click="addMealItem({{ $mealIndex }})">{{ __('missions.add_food_item') }}</button>
            </div>
        @endforeach

        <button type="button" class="btn btn-outline-light btn-sm mb-3" wire:click="addNutritionMeal">{{ __('missions.add_meal') }}</button>

        <div class="eg-mission-calorie-hint mb-3">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
            {{ __('missions.calories_ai_soon') }}
        </div>

        @error('nutritionMeals')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        <button type="submit" class="btn btn-primary">{{ __('missions.save_nutrition_day') }}</button>
    </form>
</div>

@if ($nutritionHistory->isNotEmpty())
    <div class="eg-mission-block mt-4">
        <h3 class="h6 mb-3">{{ __('missions.nutrition_history') }}</h3>
        @foreach ($nutritionHistory as $day)
            <article class="eg-mission-history-card mb-3">
                <header class="d-flex flex-wrap justify-content-between mb-2">
                    <strong>{{ $day->log_date->translatedFormat('j F Y') }}</strong>
                    @if ($day->total_calories)
                        <span class="eg-badge">{{ eg_num($day->total_calories) }} {{ __('missions.kcal') }}</span>
                    @endif
                </header>
                @foreach ($day->meals as $meal)
                    <div class="mb-2">
                        <span class="fw-semibold">{{ $meal->meal_type->label($locale) }}</span>
                        <ul class="mb-0 small eg-text-muted">
                            @foreach ($meal->items as $item)
                                <li>
                                    {{ $item->name }}
                                    @if ($item->quantity) — {{ eg_num($item->quantity) }} {{ $item->unit }} @endif
                                    @if ($item->calories) ({{ eg_num($item->calories) }} {{ __('missions.kcal') }}) @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </article>
        @endforeach
        {{ $nutritionHistory->links() }}
    </div>
@endif
