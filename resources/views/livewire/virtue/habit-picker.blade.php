<div class="eg-virtue-page">

    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                ['href' => route('virtue.hub', ['locale' => $locale]), 'label' => 'Back to Virtue Forge', 'icon' => 'fa-brain'],
            ],
        ])
    </section>

    <section class="container pb-5">

        <div class="eg-virtue-picker-header eg-glass mb-4">
            <h1 class="eg-display h4 mb-1">Choose Your Mission</h1>
            <p class="eg-text-muted mb-0">Pick a habit to transform, or describe your own for an AI-generated plan.</p>
        </div>

        {{-- Tabs --}}
        <div class="eg-virtue-tabs mb-4">
            <button type="button"
                    class="eg-virtue-tab {{ $activeTab === 'suggested' ? 'is-active' : '' }}"
                    wire:click="$set('activeTab', 'suggested')">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                Suggested Habits
            </button>
            <button type="button"
                    class="eg-virtue-tab {{ $activeTab === 'custom' ? 'is-active' : '' }}"
                    wire:click="$set('activeTab', 'custom')">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                My Own Habit
            </button>
        </div>

        {{-- Suggested tab --}}
        @if ($activeTab === 'suggested')
            @foreach ($groupedHabits as $categoryLabel => $habits)
                <div class="eg-virtue-category-group mb-4">
                    <div class="eg-virtue-category-label mb-2">
                        <span>{{ $habits->first()->category->icon() }}</span>
                        {{ $categoryLabel }}
                    </div>
                    <div class="eg-virtue-habit-list">
                        @foreach ($habits as $habit)
                            <button type="button"
                                    class="eg-virtue-habit-tile {{ $selectedHabitId === $habit->id ? 'is-selected' : '' }}"
                                    wire:click="selectHabit({{ $habit->id }})">
                                <div class="flex-1">
                                    <div class="eg-virtue-habit-tile__name">{{ $habit->name }}</div>
                                    @if ($habit->description)
                                        <div class="eg-virtue-habit-tile__desc eg-text-muted small">
                                            {{ Str::limit($habit->description, 80) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="eg-virtue-habit-tile__check" aria-hidden="true">
                                    @if ($selectedHabitId === $habit->id)
                                        <i class="fa-solid fa-circle-check"></i>
                                    @else
                                        <i class="fa-regular fa-circle"></i>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Custom tab --}}
        @if ($activeTab === 'custom')
            <div class="eg-glass p-4 mb-4">
                <h2 class="h6 mb-1">Describe your habit</h2>
                <p class="eg-text-muted small mb-3">
                    The AI will analyse its root cause and create a personalised plan for you.
                </p>

                <div class="mb-3">
                    <textarea
                        wire:model="customDescription"
                        class="eg-virtue-textarea form-control @error('customDescription') is-invalid @enderror"
                        rows="4"
                        placeholder="e.g. I always use sarcasm when I'm upset instead of saying how I feel directly..."
                    ></textarea>
                    @error('customDescription')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="button"
                        class="eg-btn eg-btn--virtue"
                        wire:click="analyzeCustomHabit"
                        wire:loading.attr="disabled"
                        @if($isAnalyzing) disabled @endif>
                    <span wire:loading.remove wire:target="analyzeCustomHabit">
                        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                        Analyse My Habit
                    </span>
                    <span wire:loading wire:target="analyzeCustomHabit">
                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                        AI is thinking…
                    </span>
                </button>
            </div>

            @if ($analyzedHabit)
                <div class="eg-virtue-ai-card eg-glass mb-4">
                    <div class="eg-virtue-ai-card__badge">
                        <i class="fa-solid fa-brain" aria-hidden="true"></i>
                        {{ $analyzedHabit['category_label'] }}
                    </div>

                    @if ($analyzedHabit['ai_root_cause'])
                        <div class="mt-3">
                            <div class="eg-virtue-ai-label mb-1">Root Cause</div>
                            <p class="mb-0">{{ $analyzedHabit['ai_root_cause'] }}</p>
                        </div>
                    @endif

                    @if (!empty($analyzedHabit['ai_steps']))
                        <div class="mt-3">
                            <div class="eg-virtue-ai-label mb-2">Your Daily Practice</div>
                            @foreach ($analyzedHabit['ai_steps'] as $step)
                                <div class="eg-virtue-step mb-2">
                                    <div class="eg-virtue-step__num">{{ $step['order'] }}</div>
                                    <div>
                                        <div class="fw-semibold">{{ $step['action'] }}</div>
                                        <div class="small eg-text-muted">{{ $step['daily_practice'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($analyzedHabit['ai_affirmation'])
                        <div class="eg-virtue-affirmation mt-3">
                            <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
                            {{ $analyzedHabit['ai_affirmation'] }}
                        </div>
                    @endif
                </div>
            @endif
        @endif

        {{-- Start routine panel --}}
        @if ($selectedHabitId)
            <div class="eg-virtue-start-panel eg-glass">
                <h3 class="h6 mb-3">
                    <i class="fa-solid fa-circle-check text-success me-1" aria-hidden="true"></i>
                    Configure your mission
                </h3>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Goal</label>
                    <div class="eg-virtue-goal-pills">
                        <button type="button"
                                class="eg-virtue-goal-pill {{ $goalType === 'days_count' && $goalTarget === 21 ? 'is-active' : '' }}"
                                wire:click="$set('goalType', 'days_count'); $set('goalTarget', 21)">
                            21 Days
                        </button>
                        <button type="button"
                                class="eg-virtue-goal-pill {{ $goalType === 'success_count' && $goalTarget === 30 ? 'is-active' : '' }}"
                                wire:click="$set('goalType', 'success_count'); $set('goalTarget', 30)">
                            30 Wins
                        </button>
                        <button type="button"
                                class="eg-virtue-goal-pill {{ $goalType === 'days_count' && $goalTarget === 90 ? 'is-active' : '' }}"
                                wire:click="$set('goalType', 'days_count'); $set('goalTarget', 90)">
                            90 Days
                        </button>
                    </div>
                </div>

                <button type="button"
                        class="eg-btn eg-btn--virtue w-100"
                        wire:click="startRoutine"
                        @if($isStarting) disabled @endif>
                    <span wire:loading.remove wire:target="startRoutine">
                        <i class="fa-solid fa-play" aria-hidden="true"></i>
                        Begin This Mission
                    </span>
                    <span wire:loading wire:target="startRoutine">
                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                        Starting…
                    </span>
                </button>
            </div>
        @endif

    </section>

</div>
