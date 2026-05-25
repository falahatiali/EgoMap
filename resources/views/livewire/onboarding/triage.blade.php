<div class="eg-triage-page">
    <section class="container py-4 py-lg-5">
        <header class="text-center mb-4 mb-lg-5 mx-auto" style="max-width: 40rem;">
            <span class="eg-badge mb-3">
                <i class="fa-solid fa-route"></i>
                {{ __('recovery.triage_title') }}
            </span>
            <h1 class="eg-display h2 mb-2">{{ __('recovery.triage_title') }}</h1>
            <p class="eg-text-muted mb-3">{{ __('recovery.triage_subtitle') }}</p>
            <p class="small eg-text-muted mb-0">
                {{ __('recovery.step_label', ['current' => $step, 'total' => 3]) }}
            </p>
            <div class="eg-triage-progress mt-3" aria-hidden="true">
                <span @class(['eg-triage-dot', 'is-active' => $step >= 1])></span>
                <span @class(['eg-triage-dot', 'is-active' => $step >= 2])></span>
                <span @class(['eg-triage-dot', 'is-active' => $step >= 3])></span>
            </div>
        </header>

        @if ($step === 1)
            <div class="eg-triage-panel mx-auto">
                <h2 class="h4 fw-semibold text-center mb-1">{{ __('recovery.q1_title') }}</h2>
                <p class="eg-text-muted text-center small mb-4">{{ __('recovery.q1_subtitle') }}</p>
                <div class="eg-triage-options">
                    @foreach ($durations as $option)
                        <button
                            type="button"
                            wire:click="selectDuration('{{ $option->value }}')"
                            class="eg-triage-option eg-transition"
                            wire:loading.attr="disabled"
                        >
                            {{ $option->label() }}
                        </button>
                    @endforeach
                </div>
            </div>
        @elseif ($step === 2)
            <div class="eg-triage-panel mx-auto">
                <button type="button" wire:click="goBack" class="eg-triage-back eg-btn-ghost btn-sm mb-3">
                    <i class="fa-solid fa-arrow-left me-1" data-icon-directional></i>
                    {{ __('recovery.start_over') }}
                </button>
                <h2 class="h4 fw-semibold text-center mb-1">{{ __('recovery.q2_title') }}</h2>
                <p class="eg-text-muted text-center small mb-4">{{ __('recovery.q2_subtitle') }}</p>
                <div class="eg-triage-options">
                    @foreach ($struggles as $option)
                        <button
                            type="button"
                            wire:click="selectStruggle('{{ $option->value }}')"
                            class="eg-triage-option eg-transition"
                            wire:loading.attr="disabled"
                        >
                            {{ $option->label() }}
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            <div class="eg-triage-result mx-auto text-center">
                @if ($recommendation !== null && $phase !== null)
                    <span class="eg-badge mb-3">{{ __('recovery.result_badge') }}</span>
                    <p class="eg-text-muted small mb-2">{{ __('recovery.result_subtitle') }}</p>
                    <p class="small fw-semibold text-uppercase mb-4" style="letter-spacing: 0.08em; color: var(--eg-accent-bright);">
                        {{ __('recovery.result_phase', ['phase' => $phase->label()]) }}
                    </p>

                    <article class="eg-triage-rec-card eg-glass">
                        <div class="eg-triage-rec-icon" aria-hidden="true">
                            <i class="fa-solid fa-{{ $recommendation['icon'] }}"></i>
                        </div>
                        <h2 class="h4 fw-semibold mb-2">{{ $recommendation['title'] }}</h2>
                        <p class="eg-text-muted mb-4">{{ $recommendation['body'] }}</p>
                        <a href="{{ $recommendation['url'] }}" class="eg-btn-primary eg-btn-pulse eg-shadow-glow px-5" wire:navigate>
                            {{ $recommendation['cta'] }}
                            <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }} ms-2" data-icon-directional></i>
                        </a>
                    </article>
                @endif
            </div>
        @endif
    </section>
</div>
