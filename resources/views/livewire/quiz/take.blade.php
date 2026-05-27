<div
    class="eg-quiz-immersive"
    wire:key="quiz-q-{{ $question->id ?? 'none' }}-{{ $session->current_sort_order }}"
    data-sound-enabled="{{ $soundEnabled ? '1' : '0' }}"
    data-option-count="{{ $isLikert ? 5 : $options->count() }}"
>
    <div class="eg-quiz-progress-rail" aria-hidden="true">
        <div class="eg-quiz-progress-rail-fill" style="width: {{ $progress }}%"></div>
    </div>

    <div class="eg-quiz-immersive-bg" aria-hidden="true">
        <div class="eg-quiz-constellation"></div>
    </div>

    <div class="container eg-quiz-immersive-inner">
        @if ($question)
            <header class="eg-quiz-topbar">
                <div class="eg-quiz-topbar-start">
                    <a href="{{ route('home') }}" class="eg-quiz-topbar-brand">
                        <span class="eg-quiz-brand-icon" aria-hidden="true">
                            <i class="fa-solid fa-compass"></i>
                        </span>
                        <div>
                            <span class="eg-quiz-counter">{{ $currentNumber }} / {{ $totalQuestions }}</span>
                            <span class="eg-quiz-counter-label">{{ __('quiz.question_label') }}</span>
                        </div>
                    </a>
                </div>

                <div class="eg-quiz-segments" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('quiz.progress') }}">
                    @for ($i = 1; $i <= $totalQuestions; $i++)
                        <span @class(['eg-quiz-segment', 'is-done' => $i < $currentNumber, 'is-current' => $i === $currentNumber])></span>
                    @endfor
                </div>

                <div class="eg-quiz-topbar-end">
                    <div>
                        <span class="eg-quiz-percent">{{ $progress }}%</span>
                        <span class="eg-quiz-percent-label">{{ __('quiz.progress') }}</span>
                    </div>
                    <button
                        type="button"
                        class="eg-quiz-sound-btn"
                        wire:click="toggleSound"
                        aria-pressed="{{ $soundEnabled ? 'true' : 'false' }}"
                        aria-label="{{ __('quiz.toggle_sound') }}"
                        title="{{ __('quiz.toggle_sound') }}"
                    >
                        <i class="fa-solid {{ $soundEnabled ? 'fa-volume-high' : 'fa-volume-xmark' }}"></i>
                    </button>
                </div>
            </header>

            @if ($isCrisis ?? false)
                @include('livewire.quiz.partials.reboot-crisis')
            @elseif ($showSafety ?? false)
                @include('livewire.quiz.partials.reboot-safety')
            @else
            <div class="eg-quiz-stage eg-quiz-animate-in">
                @if ($estimatedMinutes)
                    <div class="eg-quiz-time-pill">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ __('quiz.estimated_time', ['minutes' => $estimatedMinutes]) }}</span>
                    </div>
                @endif

                <h1 class="eg-quiz-question">
                    <span class="eg-quiz-question-num" aria-hidden="true">{{ $currentNumber }}</span>
                    <span class="eg-quiz-question-text">{{ $question->getTranslation('text', $quizLocale) }}</span>
                </h1>

                @if ($question->getTranslation('help_text', $quizLocale))
                    <p class="eg-quiz-help">{{ $question->getTranslation('help_text', $quizLocale) }}</p>
                @endif

                @if ($isLikert)
                    <div class="eg-quiz-options eg-quiz-options-grid">
                        @foreach ($likertLabels as $index => $label)
                            @php $value = (string) ($index + 1); @endphp
                            <button
                                type="button"
                                class="eg-quiz-option"
                                data-accent="{{ ['violet', 'magenta', 'purple', 'teal', 'gold'][$index] ?? 'purple' }}"
                                data-hotkey="{{ $value }}"
                                wire:click="selectAnswer('{{ $value }}')"
                                wire:loading.attr="disabled"
                                wire:target="selectAnswer"
                            >
                                <span class="eg-quiz-option-icon">{{ $value }}</span>
                                <span class="eg-quiz-option-label">{{ $label }}</span>
                                <span class="eg-quiz-option-key">{{ $value }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif ($isMultipleChoice)
                    @php
                        $maxSelections = (int) ($question->config['max_selections'] ?? 3);
                        $selectedCount = count($multiSelection ?? []);
                    @endphp
                    <p class="eg-quiz-selection-hint" aria-live="polite">
                        @if ($selectedCount === 0)
                            {{ __('quiz.select_up_to', ['max' => $maxSelections]) }}
                        @else
                            {{ __('quiz.selection_count', ['current' => $selectedCount, 'max' => $maxSelections]) }}
                        @endif
                    </p>

                    <div
                        @class([
                            'eg-quiz-options',
                            'eg-quiz-options-grid' => $options->count() >= 2,
                        ])
                    >
                        @foreach ($options as $index => $option)
                            @php
                                $accent = $option->meta['accent'] ?? 'purple';
                                $icon = $option->meta['icon'] ?? 'fa-circle';
                                $hotkey = (string) ($index + 1);
                                $optionValue = (string) $option->value;
                                $isActive = in_array($optionValue, $multiSelection ?? [], true);
                                $isLocked = $maxSelections > 0 && $selectedCount >= $maxSelections && ! $isActive;
                            @endphp
                            <button
                                type="button"
                                @class(['eg-quiz-option', 'is-active' => $isActive])
                                data-accent="{{ $accent }}"
                                data-hotkey="{{ $hotkey }}"
                                wire:click.prevent="toggleMultiChoice('{{ $optionValue }}')"
                                wire:loading.attr="disabled"
                                wire:target="toggleMultiChoice"
                                @disabled($isLocked)
                                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            >
                                <span class="eg-quiz-option-icon" aria-hidden="true">
                                    <i class="fa-solid {{ $icon }} eg-quiz-option-symbol"></i>
                                    <i class="fa-solid fa-check eg-quiz-option-check"></i>
                                </span>
                                <span class="eg-quiz-option-label">{{ $option->getTranslation('label', $quizLocale) }}</span>
                                <span class="eg-quiz-option-key">{{ $hotkey }}</span>
                                @if ($isActive)
                                    <span class="eg-quiz-option-selected-ring" aria-hidden="true"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="eg-quiz-multi-actions">
                        <button
                            type="button"
                            @class([
                                'eg-quiz-next-btn',
                                'is-muted' => $selectedCount === 0,
                            ])
                            wire:click.prevent="submitMultiChoice"
                            wire:loading.attr="disabled"
                            wire:target="toggleMultiChoice,submitMultiChoice"
                            @disabled($selectedCount === 0)
                        >
                            <i class="fa-solid fa-arrow-right" data-icon-directional></i>
                            <span>{{ __('quiz.continue') }}</span>
                        </button>
                        <button
                            type="button"
                            class="eg-quiz-skip-btn"
                            wire:click.prevent="skipQuestion"
                            wire:loading.attr="disabled"
                            wire:target="submitMultiChoice,skipQuestion"
                        >
                            <span>{{ __('quiz.skip') }}</span>
                        </button>
                    </div>
                @else
                    <div
                        @class([
                            'eg-quiz-options',
                            'eg-quiz-options-grid' => $options->count() >= 2,
                        ])
                        wire:loading.class="eg-quiz-options-busy"
                        wire:target="selectAnswer"
                    >
                        @foreach ($options as $index => $option)
                            @php
                                $accent = $option->meta['accent'] ?? 'purple';
                                $icon = $option->meta['icon'] ?? 'fa-circle';
                                $hotkey = (string) ($index + 1);
                                $optionValue = (string) $option->value;
                                $isActive = ($singleSelection ?? '') === $optionValue;
                            @endphp
                            <button
                                type="button"
                                @class(['eg-quiz-option', 'is-active' => $isActive])
                                data-accent="{{ $accent }}"
                                data-hotkey="{{ $hotkey }}"
                                wire:click.prevent="selectAnswer('{{ $optionValue }}')"
                                wire:loading.attr="disabled"
                                wire:target="selectAnswer"
                                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                            >
                                <span class="eg-quiz-option-icon" aria-hidden="true">
                                    <i class="fa-solid {{ $icon }} eg-quiz-option-symbol"></i>
                                    <i class="fa-solid fa-check eg-quiz-option-check"></i>
                                </span>
                                <span class="eg-quiz-option-label">{{ $option->getTranslation('label', $quizLocale) }}</span>
                                <span class="eg-quiz-option-key">{{ $hotkey }}</span>
                                @if ($isActive)
                                    <span class="eg-quiz-option-selected-ring" aria-hidden="true"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                <p class="eg-quiz-keyboard-hint">
                    <i class="fa-regular fa-keyboard"></i>
                    <span>
                        @if ($isMultipleChoice)
                            {{ __('quiz.keyboard_hint_multi', ['max' => $maxSelections]) }}
                        @else
                            {{ __('quiz.keyboard_hint', ['max' => $isLikert ? 5 : $options->count()]) }}
                        @endif
                    </span>
                </p>
            </div>
            @endif

            <footer class="eg-quiz-footer">
                @if ($showSafety ?? false)
                    <button
                        type="button"
                        class="eg-quiz-back-btn"
                        wire:click="cancelSafetyCheck"
                        wire:loading.attr="disabled"
                    >
                        <i class="fa-solid fa-chevron-left" data-icon-directional></i>
                        <span>{{ __('quiz.back') }}</span>
                    </button>
                @elseif ($canGoBack)
                    <button
                        type="button"
                        class="eg-quiz-back-btn"
                        wire:click="goBack"
                        wire:loading.attr="disabled"
                    >
                        <i class="fa-solid fa-chevron-left" data-icon-directional></i>
                        <span>{{ __('quiz.back') }}</span>
                    </button>
                @else
                    <span></span>
                @endif

                <a href="{{ route('home') }}" class="eg-quiz-exit-btn">
                    <i class="fa-solid fa-house"></i>
                    <span>{{ __('quiz.back_home') }}</span>
                </a>
            </footer>

            <div wire:loading wire:target="selectAnswer,submitSingleChoice,submitMultiChoice,goBack" class="eg-quiz-saving" aria-live="polite">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
        @else
            <div class="text-center text-muted py-5">{{ __('quiz.loading') }}</div>
        @endif
    </div>
</div>

@push('scripts')
    @vite('resources/js/quiz-take.js')
    <script>
        (function () {
            const uuid = @json($uuid);
            const slug = @json($session->quiz->slug ?? null);
            const key = slug ? `egomap_quiz_${slug}` : null;
            if (key && uuid) {
                localStorage.setItem(key, uuid);
            }
        })();
    </script>
@endpush
