<div class="eg-quiz-stage eg-quiz-animate-in text-center">
    <div class="eg-quiz-time-pill mx-auto" style="max-width: fit-content;">
        <i class="fa-solid fa-life-ring"></i>
        <span>{{ __('quiz.reboot.crisis_badge') }}</span>
    </div>

    <h1 class="eg-quiz-question justify-content-center">
        <span class="eg-quiz-question-text">{{ __('quiz.reboot.crisis_title') }}</span>
    </h1>
    <p class="eg-quiz-help mx-auto" style="max-width: 36rem;">{{ __('quiz.reboot.crisis_body') }}</p>

    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mt-4">
        <button type="button" class="eg-quiz-next-btn" wire:click="resetAfterCrisis">
            {{ __('quiz.reboot.crisis_reset') }}
        </button>
        <a href="{{ route('home') }}#emergency" class="eg-quiz-skip-btn text-decoration-none d-inline-flex align-items-center justify-content-center">
            {{ __('quiz.reboot.crisis_emergency') }}
        </a>
    </div>
</div>
