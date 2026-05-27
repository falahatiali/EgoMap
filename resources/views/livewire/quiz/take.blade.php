<div
    class="eg-quiz-immersive"
    wire:key="quiz-q-{{ $currentNumber }}"
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

            <div class="eg-quiz-stage eg-quiz-animate-in">
                @if ($estimatedMinutes)
                    <div class="eg-quiz-time-pill">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ __('quiz.estimated_time', ['minutes' => $estimatedMinutes]) }}</span>
                    </div>
                @endif

                <h1 class="eg-quiz-question">
                    <span class="eg-quiz-question-num" aria-hidden="true">{{ $currentNumber }}</span>
                    <span
                        class="eg-quiz-question-text"
                        data-locale-field
                        data-en="{{ $question->getTranslation('text', 'en') }}"
                        data-fa="{{ $question->getTranslation('text', 'fa') }}"
                    >{{ $question->getTranslation('text', app()->getLocale()) }}</span>
                </h1>

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
                @else
                    <div @class([
                        'eg-quiz-options',
                        'eg-quiz-options-grid' => $options->count() >= 2,
                    ])>
                        @foreach ($options as $index => $option)
                            @php
                                $accent = $option->meta['accent'] ?? 'purple';
                                $icon = $option->meta['icon'] ?? 'fa-circle';
                                $hotkey = (string) ($index + 1);
                            @endphp
                            <button
                                type="button"
                                @class(['eg-quiz-option', 'is-active' => $isMultipleChoice && in_array($option->value, $multiSelection ?? [], true)])
                                data-accent="{{ $accent }}"
                                data-hotkey="{{ $hotkey }}"
                                @if ($isMultipleChoice)
                                    wire:click="toggleMultiChoice('{{ $option->value }}')"
                                @else
                                    wire:click="selectAnswer('{{ $option->value }}')"
                                @endif
                                wire:loading.attr="disabled"
                                wire:target="{{ $isMultipleChoice ? 'toggleMultiChoice,submitMultiChoice,skipQuestion' : 'selectAnswer' }}"
                                aria-pressed="{{ $isMultipleChoice ? (in_array($option->value, $multiSelection ?? [], true) ? 'true' : 'false') : 'false' }}"
                            >
                                <span class="eg-quiz-option-icon">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </span>
                                <span
                                    class="eg-quiz-option-label"
                                    data-locale-field
                                    data-en="{{ $option->getTranslation('label', 'en') }}"
                                    data-fa="{{ $option->getTranslation('label', 'fa') }}"
                                >{{ $option->getTranslation('label', app()->getLocale()) }}</span>
                                <span class="eg-quiz-option-key">{{ $hotkey }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if ($isMultipleChoice)
                        <div class="eg-quiz-multi-actions">
                            <button
                                type="button"
                                class="eg-quiz-next-btn"
                                wire:click="submitMultiChoice"
                                wire:loading.attr="disabled"
                                wire:target="submitMultiChoice,skipQuestion"
                            >
                                <i class="fa-solid fa-arrow-right" data-icon-directional></i>
                                <span>{{ __('quiz.continue') }}</span>
                            </button>
                            <button
                                type="button"
                                class="eg-quiz-skip-btn"
                                wire:click="skipQuestion"
                                wire:loading.attr="disabled"
                                wire:target="submitMultiChoice,skipQuestion"
                            >
                                <span>{{ __('quiz.skip') }}</span>
                            </button>
                        </div>
                    @endif
                @endif

                <p class="eg-quiz-keyboard-hint">
                    <i class="fa-regular fa-keyboard"></i>
                    <span>{{ __('quiz.keyboard_hint', ['max' => $isLikert ? 5 : $options->count()]) }}</span>
                </p>
            </div>

            <footer class="eg-quiz-footer">
                @if ($canGoBack)
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

            <div wire:loading wire:target="selectAnswer,goBack" class="eg-quiz-saving" aria-live="polite">
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
