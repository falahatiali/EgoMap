<div class="eg-quiz-stage eg-quiz-animate-in">
    <div class="eg-quiz-time-pill">
        <i class="fa-solid fa-shield-heart"></i>
        <span>{{ __('quiz.reboot.safety_badge') }}</span>
    </div>

    <h1 class="eg-quiz-question">
        <span class="eg-quiz-question-text">{{ __('quiz.reboot.safety_title') }}</span>
    </h1>
    <p class="eg-quiz-help">{{ __('quiz.reboot.safety_intro') }}</p>

    <div class="eg-quiz-options eg-quiz-options-grid">
        @foreach (range(1, 4) as $index => $value)
            <button
                type="button"
                class="eg-quiz-option"
                data-accent="{{ ['emerald', 'blue', 'amber', 'rose'][$index] }}"
                wire:click="submitSafetyAnswer('{{ $value }}')"
                wire:loading.attr="disabled"
                wire:target="submitSafetyAnswer"
            >
                <span class="eg-quiz-option-icon">{{ $value }}</span>
                <span class="eg-quiz-option-label">{{ __('quiz.reboot.safety_opt_'.$value) }}</span>
            </button>
        @endforeach
    </div>
</div>
