<div
    class="eg-quiz-immersive"
    wire:key="quiz-step-{{ $session->current_sort_order }}"
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
            <div
                class="eg-quiz-stage eg-quiz-animate-in"
                wire:key="quiz-stage-{{ $session->current_sort_order }}-{{ $question->id }}"
            >
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
                    @include('livewire.quiz.partials.likert-options')
                @elseif ($isMultipleChoice)
                    @include('livewire.quiz.partials.multiple-choice')
                @else
                    @include('livewire.quiz.partials.single-choice-options')
                @endif

                <p class="eg-quiz-keyboard-hint">
                    <i class="fa-regular fa-keyboard"></i>
                    <span>
                        @if ($isMultipleChoice)
                            {{ __('quiz.keyboard_hint_multi', ['max' => (int) ($question->config['max_selections'] ?? 3)]) }}
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

            <div wire:loading wire:target="selectAnswer,toggleMultiChoice,submitMultiChoice,goBack" class="eg-quiz-saving" aria-live="polite">
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
