<div class="eg-triage-page eg-triage-page--fullscreen">
    @if ($step <= 4)
        <div class="eg-triage-progress-top" aria-hidden="true">
            <span @class(['eg-triage-dot', 'is-active' => $questionStep >= 1])></span>
            <span @class(['eg-triage-dot', 'is-active' => $questionStep >= 2])></span>
            <span @class(['eg-triage-dot', 'is-active' => $questionStep >= 3])></span>
            <span @class(['eg-triage-dot', 'is-active' => $questionStep >= 4])></span>
        </div>

        <p class="eg-triage-step-label text-center">
            {{ __('recovery.question_label', [
                'current' => eg_num($questionStep),
                'total' => eg_num($questionTotal),
            ]) }}
        </p>
    @endif

    @if ($step === 1)
        <div class="eg-triage-panel mx-auto" wire:transition>
            <h1 class="eg-triage-question">{{ __('recovery.q_relationship_title') }}</h1>
            <p class="eg-triage-hint">{{ __('recovery.q_relationship_subtitle') }}</p>
            <div class="eg-triage-options">
                @foreach ($relationshipDurations as $option)
                    <button
                        type="button"
                        wire:click="selectRelationshipDuration('{{ $option->value }}')"
                        class="eg-triage-option eg-transition"
                        wire:loading.attr="disabled"
                    >
                        {{ $option->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($step === 2)
        <div class="eg-triage-panel mx-auto" wire:transition>
            <button type="button" wire:click="goBack" class="eg-triage-back eg-btn-ghost btn-sm mb-3">
                <i class="fa-solid fa-arrow-left me-1" data-icon-directional></i>
                {{ __('recovery.back') }}
            </button>
            <h1 class="eg-triage-question">{{ __('recovery.q1_title') }}</h1>
            <p class="eg-triage-hint">{{ __('recovery.q1_subtitle') }}</p>
            <div class="eg-triage-options">
                @foreach ($breakupDurations as $option)
                    <button
                        type="button"
                        wire:click="selectBreakupDuration('{{ $option->value }}')"
                        class="eg-triage-option eg-transition"
                        wire:loading.attr="disabled"
                    >
                        {{ $option->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($step === 3)
        <div class="eg-triage-panel mx-auto" wire:transition>
            <button type="button" wire:click="goBack" class="eg-triage-back eg-btn-ghost btn-sm mb-3">
                <i class="fa-solid fa-arrow-left me-1" data-icon-directional></i>
                {{ __('recovery.back') }}
            </button>
            <h1 class="eg-triage-question">{{ __('recovery.q3_title') }}</h1>
            <p class="eg-triage-hint">{{ __('recovery.q3_subtitle') }}</p>
            <div class="eg-triage-options">
                @foreach ($initiators as $option)
                    <button
                        type="button"
                        wire:click="selectInitiator('{{ $option->value }}')"
                        class="eg-triage-option eg-transition"
                        wire:loading.attr="disabled"
                    >
                        {{ $option->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($step === 4)
        <div class="eg-triage-panel mx-auto" wire:transition>
            <button type="button" wire:click="goBack" class="eg-triage-back eg-btn-ghost btn-sm mb-3">
                <i class="fa-solid fa-arrow-left me-1" data-icon-directional></i>
                {{ __('recovery.back') }}
            </button>
            <h1 class="eg-triage-question">{{ __('recovery.q2_title') }}</h1>
            <p class="eg-triage-hint">{{ __('recovery.q2_subtitle') }}</p>
            <div class="eg-triage-options">
                @foreach ($struggles as $option)
                    <button
                        type="button"
                        wire:click="selectStruggle('{{ $option->value }}')"
                        class="eg-triage-option eg-transition"
                        wire:loading.attr="disabled"
                        wire:target="selectStruggle"
                    >
                        <span wire:loading.remove wire:target="selectStruggle">{{ $option->label() }}</span>
                        <span wire:loading wire:target="selectStruggle">…</span>
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($step === 5)
        <div
            class="eg-triage-loading mx-auto text-center"
            wire:transition
            x-data
            x-init="setTimeout(() => $wire.finishDiagnosis(), 2600)"
        >
            <div class="eg-triage-loading-ring" aria-hidden="true"></div>
            <h2 class="h5 fw-semibold mb-2">{{ __('recovery.diagnosis_loading_title') }}</h2>
            <p class="eg-text-muted small mb-0">{{ __('recovery.diagnosis_loading_body') }}</p>
        </div>
    @else
        <div class="eg-triage-result mx-auto" wire:transition>
            @if ($actionPlan !== null && $phase !== null)
                <span class="eg-triage-status-badge">{{ $actionPlan['status_label'] }}</span>

                <article class="eg-triage-diagnosis eg-glass mb-4">
                    <h2 class="h5 fw-semibold mb-2">{{ $actionPlan['diagnosis_title'] }}</h2>
                    <p class="eg-text-muted small mb-0">{{ $actionPlan['diagnosis_body'] }}</p>
                </article>

                <article class="eg-triage-rec-card eg-glass">
                    <p class="eg-triage-priority-label mb-2">{{ __('recovery.plan_priority_label') }}</p>
                    <div class="eg-triage-rec-icon" aria-hidden="true">
                        <i class="fa-solid fa-{{ $actionPlan['icon'] }}"></i>
                    </div>
                    <h2 class="h4 fw-semibold mb-2">{{ $actionPlan['priority_title'] }}</h2>
                    <p class="eg-text-muted mb-4">{{ $actionPlan['priority_why'] }}</p>
                    <a href="{{ $actionPlan['url'] }}" class="eg-btn-protocol eg-btn-pulse eg-shadow-glow px-5 w-100" wire:navigate>
                        {{ $actionPlan['cta'] }}
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }} ms-2" data-icon-directional></i>
                    </a>
                </article>

                <p class="text-center eg-text-muted small mt-4 mb-0">
                    {{ __('recovery.plan_footer', ['phase' => $phase->label()]) }}
                </p>
            @endif
        </div>
    @endif
</div>
