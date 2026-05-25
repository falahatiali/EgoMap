<div class="eg-no-contact-page" wire:poll.30s>
    <section class="container pt-3 pb-2">
        @php
            $pageNavLinks = [
                [
                    'href' => route('home'),
                    'label' => __('no_contact.back_home'),
                    'icon' => 'fa-house',
                ],
            ];

            if (auth()->check()) {
                $pageNavLinks[] = [
                    'href' => route('profile'),
                    'label' => __('profile.page_title'),
                    'icon' => 'fa-user',
                ];
            }
        @endphp

        @include('partials.page-nav-actions', ['links' => $pageNavLinks])
    </section>

    <section class="container pb-5">
        <header class="text-center mb-4 mb-lg-5">
            <span class="eg-badge mb-3">
                <i class="fa-solid fa-shield-halved"></i>
                {{ __('no_contact.page_title') }}
            </span>
            <h1 class="eg-display h2 mb-2">{{ __('no_contact.page_title') }}</h1>
            <p class="eg-text-muted mx-auto mb-0" style="max-width: 46ch;">{{ __('no_contact.page_subtitle') }}</p>
        </header>

        @if ($errors->any())
            <div class="alert alert-danger border-0 eg-glass mb-4" role="alert">
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($state['mode'] === 'setup')
            <div class="eg-nc-setup mx-auto">
                <div class="text-center mb-4">
                    <span class="eg-badge mb-3">
                        <i class="fa-solid fa-hourglass-start"></i>
                        {{ __('no_contact.setup_badge') }}
                    </span>
                    <h2 class="h3 fw-semibold mb-2">{{ __('no_contact.setup_title') }}</h2>
                    <p class="eg-text-muted small mb-0">{{ __('no_contact.setup_subtitle') }}</p>
                </div>

                <div class="eg-nc-preset-grid mb-4">
                    @foreach ($presets as $preset)
                        <button
                            type="button"
                            wire:click="$set('selectedDays', {{ $preset['days'] }})"
                            @class([
                                'eg-nc-preset',
                                'eg-nc-preset--selected' => $selectedDays === $preset['days'],
                                'eg-nc-preset--recommended' => $preset['recommended'],
                            ])
                        >
                            @if ($preset['recommended'])
                                <span class="eg-nc-preset-tag">{{ __('no_contact.recommended') }}</span>
                            @endif
                            <span class="eg-nc-preset-days">{{ $preset['label'] }}</span>
                            <span class="eg-nc-preset-desc">{{ $preset['description'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="text-center">
                    <button type="button" wire:click="startProtocol" class="eg-btn-primary eg-btn-pulse eg-shadow-glow px-5" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="startProtocol">
                            <i class="fa-solid fa-lock me-2"></i>
                            {{ __('no_contact.start_protocol') }}
                        </span>
                        <span wire:loading wire:target="startProtocol">…</span>
                    </button>
                </div>
            </div>
        @elseif ($state['mode'] === 'active')
            <div
                id="eg-no-contact-timer"
                class="eg-nc-active mx-auto"
                data-target-ends-at="{{ $state['target_ends_at'] }}"
                data-streak-started-at="{{ $state['streak_started_at'] }}"
                data-server-now="{{ $state['server_now'] }}"
                wire:key="nc-timer-{{ $state['protocol_uuid'] }}"
            >
                <div class="eg-nc-ring-wrap mb-4">
                    <svg class="eg-nc-ring" viewBox="0 0 120 120" aria-hidden="true">
                        <circle class="eg-nc-ring-track" cx="60" cy="60" r="54" />
                        <circle
                            class="eg-nc-ring-progress"
                            cx="60"
                            cy="60"
                            r="54"
                            style="--eg-nc-progress: {{ $state['progress_percent'] }};"
                        />
                    </svg>
                    <div class="eg-nc-ring-center">
                        <span class="eg-nc-ring-label">{{ __('no_contact.remaining_label') }}</span>
                        <div class="eg-nc-countdown" aria-live="polite">
                            <div class="eg-nc-countdown-row eg-nc-countdown-row--primary">
                                <div class="eg-nc-unit">
                                    <span class="eg-nc-unit-value" data-nc-part="days">{{ str_pad((string) $state['countdown']['days'], 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="eg-nc-unit-label">{{ __('no_contact.unit_days') }}</span>
                                </div>
                            </div>
                            <div class="eg-nc-countdown-row">
                                <div class="eg-nc-unit">
                                    <span class="eg-nc-unit-value" data-nc-part="hours">{{ str_pad((string) $state['countdown']['hours'], 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="eg-nc-unit-label">h</span>
                                </div>
                                <span class="eg-nc-sep">:</span>
                                <div class="eg-nc-unit">
                                    <span class="eg-nc-unit-value" data-nc-part="minutes">{{ str_pad((string) $state['countdown']['minutes'], 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="eg-nc-unit-label">m</span>
                                </div>
                                <span class="eg-nc-sep">:</span>
                                <div class="eg-nc-unit">
                                    <span class="eg-nc-unit-value" data-nc-part="seconds">{{ str_pad((string) $state['countdown']['seconds'], 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="eg-nc-unit-label">s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="eg-nc-stats row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="eg-nc-stat eg-glass">
                            <span class="eg-nc-stat-label">{{ __('no_contact.active_badge') }}</span>
                            <span class="eg-nc-stat-value">{{ __('no_contact.days', ['count' => $state['duration_days']]) }}</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="eg-nc-stat eg-glass">
                            <span class="eg-nc-stat-label">{{ __('no_contact.stat_elapsed') }}</span>
                            <span class="eg-nc-stat-value">{{ $state['elapsed_label'] }}</span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="eg-nc-stat eg-glass">
                            <span class="eg-nc-stat-label">{{ __('no_contact.stat_slips') }}</span>
                            <span class="eg-nc-stat-value">{{ trans_choice('no_contact.slip_count', $state['slip_count'], ['count' => $state['slip_count']]) }}</span>
                        </div>
                    </div>
                </div>

                <div class="eg-nc-rules eg-glass mb-4">
                    <h3 class="h6 fw-semibold mb-3">{{ __('no_contact.rules_title') }}</h3>
                    <ul class="eg-nc-rules-list mb-0">
                        <li>{{ __('no_contact.rule_1') }}</li>
                        <li>{{ __('no_contact.rule_2') }}</li>
                        <li>{{ __('no_contact.rule_3') }}</li>
                    </ul>
                </div>

                <p class="text-center eg-text-muted small mb-3">{{ __('no_contact.slip_warning') }}</p>

                @if ($confirmSlip)
                    <div class="eg-nc-slip-confirm eg-glass mb-3">
                        <h3 class="h6 fw-semibold mb-2">{{ __('no_contact.slip_confirm_title') }}</h3>
                        <p class="eg-text-muted small mb-3">{{ __('no_contact.slip_confirm_body') }}</p>
                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                            <button type="button" wire:click="recordSlip" class="eg-btn-danger eg-transition" wire:loading.attr="disabled">
                                {{ __('no_contact.slip_confirm_yes') }}
                            </button>
                            <button type="button" wire:click="cancelSlipConfirm" class="eg-btn-ghost eg-transition">
                                {{ __('no_contact.slip_confirm_no') }}
                            </button>
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <button type="button" wire:click="recordSlip" class="eg-nc-slip-btn eg-transition">
                            <i class="fa-solid fa-rotate-left me-2"></i>
                            {{ __('no_contact.slip_button') }}
                        </button>
                    </div>
                @endif
            </div>
        @else
            <div class="eg-nc-completed eg-glass mx-auto text-center">
                <div class="eg-nc-completed-icon mb-3" aria-hidden="true">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <span class="eg-badge mb-3">{{ __('no_contact.completed_badge') }}</span>
                <h2 class="h3 fw-semibold mb-2">{{ __('no_contact.completed_title') }}</h2>
                <p class="eg-text-muted mb-4">
                    {{ __('no_contact.completed_subtitle', ['days' => $state['duration_days']]) }}
                </p>
                <button type="button" wire:click="restartAfterComplete" class="eg-btn-primary eg-transition">
                    {{ __('no_contact.start_again') }}
                </button>
            </div>
        @endif
    </section>
</div>
