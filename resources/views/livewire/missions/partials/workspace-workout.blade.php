@if ($requiresProWorkout)
    <div class="eg-mission-pro-banner d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <strong>{{ __('missions.ai_workout') }}</strong>
            <p class="small mb-0 eg-text-muted">{{ __('missions.pro_hint') }}</p>
        </div>
        <button type="button" class="btn btn-sm btn-warning" disabled>{{ __('missions.pro_cta') }}</button>
    </div>
@endif

<div class="eg-mission-block mb-4">
    <h2 class="eg-mission-block-title">{{ __('missions.workout_weekly_plan') }}</h2>
    <p class="eg-text-muted small">{{ __('missions.workout_weekly_plan_help') }}</p>
    <form wire:submit="saveWorkoutPlan" class="mb-3">
        @foreach ($workoutPlan as $index => $row)
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-md-3">
                    <select class="form-select" wire:model="workoutPlan.{{ $index }}.day">
                        @foreach ($dayOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" wire:model="workoutPlan.{{ $index }}.focus" placeholder="{{ __('missions.workout_focus') }}">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" wire:model="workoutPlan.{{ $index }}.notes">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeWorkoutPlanRow({{ $index }})"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        @endforeach
        <button type="button" class="btn btn-outline-light btn-sm me-2" wire:click="addWorkoutPlanRow">{{ __('missions.workout_add_row') }}</button>
        <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('missions.save_plan') }}</button>
    </form>
</div>

<div class="eg-mission-block">
    <h2 class="eg-mission-block-title">{{ __('missions.workout_session_log') }}</h2>
    <p class="eg-text-muted small mb-3">{{ __('missions.workout_session_log_help') }}</p>

    <form wire:submit="saveWorkoutSession">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.workout_day') }}</label>
                <select class="form-select" wire:model="workoutDayKey">
                    @foreach ($dayOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.workout_focus') }}</label>
                <input type="text" class="form-control" wire:model="workoutFocus" placeholder="{{ __('missions.workout_focus_placeholder') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.daily_duration') }}</label>
                <input type="number" class="form-control" wire:model="workoutDuration" min="1">
            </div>
        </div>

        @foreach ($workoutExercises as $exIndex => $exercise)
            <div class="eg-mission-exercise-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">{{ __('missions.exercise_name') }} {{ eg_num($exIndex + 1) }}</label>
                    @if (count($workoutExercises) > 1)
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeWorkoutExercise({{ $exIndex }})">{{ __('missions.workout_remove') }}</button>
                    @endif
                </div>
                <input type="text" class="form-control mb-2" wire:model="workoutExercises.{{ $exIndex }}.name" placeholder="{{ __('missions.exercise_placeholder') }}">
                <div class="table-responsive">
                    <table class="table table-sm eg-mission-sets-table mb-2">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('missions.set_reps') }}</th>
                                <th>{{ __('missions.set_weight') }}</th>
                                <th>{{ __('missions.workout_notes') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exercise['sets'] as $setIndex => $set)
                                <tr>
                                    <td>{{ eg_num($setIndex + 1) }}</td>
                                    <td><input type="number" class="form-control form-control-sm" wire:model="workoutExercises.{{ $exIndex }}.sets.{{ $setIndex }}.reps" min="1"></td>
                                    <td><input type="number" step="0.5" class="form-control form-control-sm" wire:model="workoutExercises.{{ $exIndex }}.sets.{{ $setIndex }}.weight"></td>
                                    <td><input type="text" class="form-control form-control-sm" wire:model="workoutExercises.{{ $exIndex }}.sets.{{ $setIndex }}.notes"></td>
                                    <td>
                                        @if (count($exercise['sets']) > 1)
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="removeWorkoutSet({{ $exIndex }}, {{ $setIndex }})">×</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-light" wire:click="addWorkoutSet({{ $exIndex }})">{{ __('missions.add_set') }}</button>
            </div>
        @endforeach

        <button type="button" class="btn btn-outline-light btn-sm mb-3" wire:click="addWorkoutExercise">{{ __('missions.add_exercise') }}</button>

        <div class="mb-3">
            <label class="form-label">{{ __('missions.session_notes') }}</label>
            <textarea class="form-control" rows="2" wire:model="workoutSessionNotes"></textarea>
        </div>

        @error('workoutExercises')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        <button type="submit" class="btn btn-primary">{{ __('missions.save_workout_session') }}</button>
    </form>
</div>

@if ($workoutHistory->isNotEmpty())
    <div class="eg-mission-block mt-4">
        <h3 class="h6 mb-3">{{ __('missions.workout_history') }}</h3>
        @foreach ($workoutHistory as $session)
            <article class="eg-mission-history-card mb-3">
                <header class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <strong>{{ $session->session_date->locale($locale)->translatedFormat($locale === 'fa' ? 'l، j F Y' : 'l, F j, Y') }}</strong>
                    @if ($session->focus)
                        <span class="eg-badge">{{ \Modules\MissionEngine\Support\MissionLocalizedText::forLocale($session->focus, $locale) }}</span>
                    @endif
                </header>
                @foreach ($session->exercises as $exercise)
                    <div class="mb-2">
                        <span class="fw-semibold">{{ \Modules\MissionEngine\Support\MissionLocalizedText::forLocale($exercise->name, $locale) }}</span>
                        <ul class="eg-mission-set-summary mb-0">
                            @foreach ($exercise->sets as $set)
                                <li>{{ __('missions.set_line', ['n' => $set->set_number, 'reps' => $set->reps ?? '—', 'weight' => $set->weight ?? '—']) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </article>
        @endforeach
        {{ $workoutHistory->links() }}
    </div>
@endif
