@php
    use App\Services\Locale\LocaleDigitFormatter;
    use App\Support\DurationFormatter;
    use App\Support\LocaleConfig;

    $digits = app(LocaleDigitFormatter::class);
    $locale = app()->getLocale();
    $isRtl = LocaleConfig::isRtl($locale);

    $pad2 = fn (int $value): string => $digits->pad($value, 2, $locale);
@endphp

<div
    class="eg-no-contact-page eg-ghost-mode-page"
    data-locale="{{ $locale }}"
    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
    wire:poll.30s
>
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

    <section class="eg-gm-container pb-5">
        @if ($errors->any())
            <div class="alert alert-danger border-0 eg-glass mb-4 mx-auto eg-gm-shell" role="alert">
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($state['mode'] === 'setup')
            <div class="eg-gm-shell eg-gm-shell--narrow text-center">
                <header class="eg-gm-header mb-4">
                    <div class="eg-gm-icon" aria-hidden="true">
                        <i class="fa-solid fa-ghost"></i>
                    </div>
                    <p class="eg-gm-kicker mb-2">{{ __('no_contact.page_title') }}</p>
                    <span class="eg-gm-status eg-gm-status--idle">{{ __('no_contact.status_not_started') }}</span>
                    <p class="eg-gm-tagline mt-3 mb-0">{{ __('no_contact.page_subtitle') }}</p>
                </header>

                <div class="eg-gm-card eg-glass">
                    <span class="eg-badge mb-3">
                        <i class="fa-solid fa-hourglass-start"></i>
                        {{ __('no_contact.setup_badge') }}
                    </span>
                    <h2 class="h4 fw-semibold mb-2">{{ __('no_contact.setup_title') }}</h2>
                    <p class="eg-text-muted small mb-4">{{ __('no_contact.setup_subtitle') }}</p>

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

                    <button type="button" wire:click="startProtocol" class="eg-gm-activate-btn" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="startProtocol">
                            <i class="fa-solid fa-ghost me-2"></i>
                            {{ __('no_contact.start_protocol') }}
                        </span>
                        <span wire:loading wire:target="startProtocol">…</span>
                    </button>
                </div>
            </div>
        @elseif ($state['mode'] === 'active')
            <div class="eg-gm-shell eg-gm-layout">
                <div class="eg-gm-layout__command">
                    @include('livewire.no-contact.partials.gamification-panel')
                </div>

                <div class="eg-gm-dashboard">
                    <div class="eg-gm-dashboard__main">
                        @auth
                            @include('livewire.no-contact.partials.ghost-daily-hub')
                        @endauth

                        @if ($pendingPunishment)
                            @include('livewire.no-contact.partials.pending-punishment', ['pending' => $pendingPunishment])
                        @endif

                        @if ($gentleMissedCheckin)
                            <div class="eg-gm-gentle-banner eg-glass mb-4" role="status">
                                <i class="fa-solid fa-heart me-2" aria-hidden="true"></i>
                                <p class="mb-0 small">{{ $gentleMissedCheckin }}</p>
                            </div>
                        @endif

                        @if ($rewardToasts !== [])
                            <div class="eg-gm-reward-notices mb-4" aria-live="polite">
                                @foreach ($rewardToasts as $toastIndex => $toast)
                                    @php $toastUi = $toast['toast'] ?? []; @endphp
                                    <div class="eg-gm-reward-notice eg-glass eg-gm-reward-notice--{{ $toastUi['tone'] ?? 'neutral' }}" wire:key="reward-toast-{{ $toastIndex }}">
                                        <div class="eg-gm-reward-notice__icon" aria-hidden="true">
                                            @if (($toastUi['tone'] ?? '') === 'reward')
                                                <i class="fa-solid fa-trophy"></i>
                                            @elseif (($toastUi['tone'] ?? '') === 'penalty')
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                            @else
                                                <i class="fa-solid fa-sparkles"></i>
                                            @endif
                                        </div>
                                        <div class="eg-gm-reward-notice__body">
                                            <p class="eg-gm-reward-notice__headline mb-1">{{ $toastUi['headline'] ?? '' }}</p>
                                            <p class="small eg-text-muted mb-0">{{ $toastUi['subtitle'] ?? ($toast['message'] ?? '') }}</p>
                                        </div>
                                        <div class="eg-gm-reward-notice__deltas small">
                                            @if (($toast['points_delta'] ?? 0) !== 0)
                                                <span @class(['eg-gm-delta', 'eg-gm-delta--pos' => ($toast['points_delta'] ?? 0) > 0, 'eg-gm-delta--neg' => ($toast['points_delta'] ?? 0) < 0])>
                                                    {{ ($toast['points_delta'] ?? 0) > 0 ? '+' : '' }}{{ eg_num($toast['points_delta'] ?? 0) }} pts
                                                </span>
                                            @endif
                                            @if (($toast['coins_delta'] ?? 0) !== 0)
                                                <span @class(['eg-gm-delta', 'eg-gm-delta--pos' => ($toast['coins_delta'] ?? 0) > 0, 'eg-gm-delta--neg' => ($toast['coins_delta'] ?? 0) < 0])>
                                                    <i class="fa-solid fa-coins"></i> {{ ($toast['coins_delta'] ?? 0) > 0 ? '+' : '' }}{{ eg_num($toast['coins_delta'] ?? 0) }}
                                                </span>
                                            @endif
                                        </div>
                                        <button type="button" class="eg-gm-reward-notice__close" wire:click="dismissRewardToast({{ $toastIndex }})" aria-label="Dismiss">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

            @php
                $elapsed = (int) ($state['elapsed_seconds'] ?? 0);
                $remaining = (int) ($state['remaining_seconds'] ?? 0);
                $elapsedDays = intdiv($elapsed, 86400);
                $elapsedHours = intdiv($elapsed % 86400, 3600);
                $elapsedMinutes = intdiv($elapsed % 3600, 60);
                $elapsedSeconds = $elapsed % 60;
                $day = $elapsedDays + 1;
                $totalDays = (int) ($state['duration_days'] ?? 90);
                $shieldPercent = (int) ($state['progress_percent'] ?? 0);
                $penaltyDays = (int) config('recovery_ai.slip_penalty_days', 5);
            @endphp

            <div
                class="eg-gm-timer-block"
                id="eg-ghost-mode-timer"
                data-ghost-started-at="{{ $state['streak_started_at'] }}"
                data-ghost-ends-at="{{ $state['target_ends_at'] }}"
                data-ghost-server-now="{{ $state['server_now'] }}"
                data-digit-locale="{{ $locale }}"
                data-duration-pattern="{{ DurationFormatter::pattern($locale) }}"
                wire:key="ghost-timer-{{ $state['protocol_uuid'] }}"
            >
                <header class="eg-gm-header mb-4">
                    <div class="eg-gm-icon" aria-hidden="true">
                        <i class="fa-solid fa-ghost"></i>
                    </div>
                    <p class="eg-gm-kicker mb-2">{{ __('no_contact.page_title') }}</p>
                    <span class="eg-gm-status eg-gm-status--active">{{ __('no_contact.status_active') }}</span>
                    <p class="eg-gm-tagline mt-3 mb-0">{{ __('no_contact.page_subtitle') }}</p>
                </header>

                <div class="eg-gm-shield-card eg-glass mb-4">
                    <div class="eg-gm-shield-ring" style="--shield-progress: {{ $shieldPercent }};">
                        <div class="eg-gm-shield-ring__inner">
                            <span class="eg-gm-shield-ring__label">{{ __('no_contact.shield_title') }}</span>
                            <strong class="eg-gm-shield-ring__value">{{ __('no_contact.shield_percent', ['percent' => eg_num($shieldPercent)]) }}</strong>
                            <span class="eg-gm-shield-ring__day">{{ __('no_contact.day_of', ['day' => eg_num($day), 'total' => eg_num($totalDays)]) }}</span>
                        </div>
                    </div>
                </div>

                @if ($showPremiumUpsell && $premiumUpsell)
                    @include('livewire.no-contact.partials.premium-upsell', ['upsell' => $premiumUpsell])
                @endif

                <div class="eg-gm-card eg-glass eg-gm-timer-card mb-4">
                    <div class="eg-gm-timer-glow" aria-hidden="true"></div>

                    <div class="eg-gm-timer-display" dir="ltr" aria-live="polite">
                        <div class="eg-gm-timer-unit">
                            <span class="eg-gm-timer-value" data-ghost-part="days">{{ $digits->format($elapsedDays, $locale) }}</span>
                            <span class="eg-gm-timer-label">{{ __('no_contact.unit_days') }}</span>
                        </div>
                        <span class="eg-gm-timer-sep">{{ __('no_contact.timer_sep') }}</span>
                        <div class="eg-gm-timer-unit">
                            <span class="eg-gm-timer-value" data-ghost-part="hours">{{ $pad2($elapsedHours) }}</span>
                            <span class="eg-gm-timer-label">{{ __('no_contact.unit_hours') }}</span>
                        </div>
                        <span class="eg-gm-timer-sep">{{ __('no_contact.timer_sep') }}</span>
                        <div class="eg-gm-timer-unit">
                            <span class="eg-gm-timer-value" data-ghost-part="minutes">{{ $pad2($elapsedMinutes) }}</span>
                            <span class="eg-gm-timer-label">{{ __('no_contact.unit_minutes') }}</span>
                        </div>
                        <span class="eg-gm-timer-sep">{{ __('no_contact.timer_sep') }}</span>
                        <div class="eg-gm-timer-unit eg-gm-timer-unit--seconds">
                            <span class="eg-gm-timer-value" data-ghost-part="seconds">{{ $pad2($elapsedSeconds) }}</span>
                            <span class="eg-gm-timer-label">{{ __('no_contact.unit_seconds') }}</span>
                        </div>
                    </div>
                </div>

                <ul class="eg-gm-metrics mb-4">
                    <li class="eg-gm-metric">
                        <bdi class="eg-gm-metric-line">
                            <span class="eg-gm-metric-label">{{ __('no_contact.stat_elapsed') }}</span><span class="eg-gm-metric-colon" aria-hidden="true">:</span>
                            <span class="eg-gm-metric-value" data-ghost-metric="streak" dir="auto">{{ DurationFormatter::formatDaysHoursMinutes($elapsed, $locale) }}</span>
                        </bdi>
                    </li>
                    <li class="eg-gm-metric">
                        <bdi class="eg-gm-metric-line">
                            <span class="eg-gm-metric-label">{{ __('no_contact.remaining_label') }}</span><span class="eg-gm-metric-colon" aria-hidden="true">:</span>
                            <span class="eg-gm-metric-value" data-ghost-metric="remaining" dir="auto">{{ DurationFormatter::formatDaysHoursMinutes($remaining, $locale) }}</span>
                        </bdi>
                    </li>
                </ul>

                <button type="button" class="eg-gm-emergency-btn mb-4" wire:click="openEmergency" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="openEmergency">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        {{ __('no_contact.emergency_button') }}
                    </span>
                    <span wire:loading wire:target="openEmergency">…</span>
                </button>

                @if ($showEmergencyPanel && $emergencySupport)
                    <div class="eg-gm-emergency-panel eg-glass mb-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <h3 class="h5 mb-0">{{ __('no_contact.emergency_title') }}</h3>
                            <button type="button" class="eg-gm-modal-x" wire:click="closeEmergency" aria-label="{{ __('no_contact.emergency_close') }}">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <p class="eg-text-muted small">{{ __('no_contact.emergency_body') }}</p>
                        <p class="small fw-semibold mb-2">{{ __('no_contact.emergency_hear_you') }}</p>

                        <div
                            class="eg-gm-breath-ring mb-2"
                            data-ghost-breath-ring
                            data-breath-wire-id="{{ $this->getId() }}"
                            x-data="ghostBreathingRing($wire)"
                            x-init="init()"
                            role="button"
                            tabindex="0"
                            @click="tap()"
                            @keydown.enter.prevent="tap()"
                            :class="{ 'is-inhale': phase === 'inhale', 'is-exhale': phase === 'exhale', 'is-success': completed }"
                            aria-label="{{ __('no_contact.emergency_breathing') }}"
                        >
                            <span class="eg-gm-breath-ring__label" x-text="phaseLabel"></span>
                        </div>
                        <p class="small text-center eg-text-muted mb-3">{{ __('no_contact.emergency_breathing_tap') }}</p>

                        @if ($hasVoicePerk)
                            <div class="mb-3" x-data="ghostPanicVoice()" x-init="init()">
                                <button type="button" class="btn btn-sm eg-btn-ghost" @click="toggleRecord()">
                                    <span x-show="!recording">{{ __('no_contact.voice_record') }}</span>
                                    <span x-show="recording">{{ __('no_contact.voice_stop') }}</span>
                                </button>
                                <button type="button" class="btn btn-sm eg-btn-ghost ms-2" @click="play()" x-show="hasRecording">{{ __('no_contact.voice_play') }}</button>
                            </div>
                        @endif

                        <p class="eg-gm-emergency-message">{{ $emergencySupport['message'] ?? '' }}</p>
                        @if (! empty($emergencySupport['exercise']))
                            <div class="eg-gm-emergency-exercise mt-3">
                                <span class="small text-muted d-block mb-1">{{ __('no_contact.emergency_exercise') }}</span>
                                <span>{{ $emergencySupport['exercise'] }}</span>
                            </div>
                        @endif

                        @if ($showPanicChallenge && $emergencyBreathingComplete)
                            <div class="eg-gm-panic-challenge mt-3 p-3 rounded">
                                <p class="small mb-2">{{ __('no_contact.panic_challenge_prompt') }}</p>
                                <button type="button" class="eg-gm-shield-btn eg-gm-shield-btn--primary w-100" wire:click="completePanicChallenge">
                                    {{ __('no_contact.panic_challenge_done') }}
                                </button>
                            </div>
                        @endif

                        @if (! $emergencyBreathingComplete)
                            <button type="button" class="eg-gm-shield-btn eg-gm-shield-btn--primary eg-transition mt-3 w-100" wire:click="completeEmergency">
                                <i class="fa-solid fa-lungs me-2"></i>
                                {{ __('no_contact.emergency_complete') }}
                            </button>
                        @endif
                    </div>
                @endif

                <div class="eg-gm-blackhole eg-glass mb-4">
                    <h3 class="h5 mb-1">{{ __('no_contact.blackhole_title') }}</h3>
                    <p class="eg-text-muted small mb-2">{{ __('no_contact.blackhole_subtitle') }}</p>

                    <div class="eg-gm-blackhole-tier mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ __('no_contact.blackhole_tier', ['tier' => eg_num($blackholeProgress['tier'])]) }}</span>
                            <span>{{ __('no_contact.blackhole_streak', ['days' => eg_num($blackholeProgress['streak_days'])]) }}</span>
                        </div>
                        <div class="progress eg-gm-tier-bar" style="height: 6px;">
                            <div class="progress-bar" style="width: {{ ($blackholeProgress['tier_progress'] / 5) * 100 }}%"></div>
                        </div>
                        <p class="small eg-text-muted mt-1 mb-0">{{ __('no_contact.blackhole_tier_next', ['writes' => eg_num(5 - $blackholeProgress['tier_progress'])]) }}</p>
                    </div>

                    @if ($blackholePhase === 'analyzed' && $blackholeResult)
                        <div class="eg-gm-blackhole-result mb-3 is-visible">
                            <div class="eg-gm-blackhole-result__metric">
                                <span class="small text-muted">{{ __('no_contact.blackhole_regret') }}</span>
                                <strong>{{ eg_num((int) ($blackholeResult['regret_probability'] ?? 0)) }}%</strong>
                            </div>
                            @if ((int) ($blackholeResult['regret_probability'] ?? 0) >= 70)
                                <p class="small text-warning mb-2">{{ __('no_contact.blackhole_high_risk') }}</p>
                            @endif
                            <p class="small mb-2"><strong>{{ __('no_contact.blackhole_emotions') }}:</strong> {{ $blackholeResult['dominant_emotions'] ?? '' }}</p>
                            <p class="mb-2">{{ $blackholeResult['analysis'] ?? '' }}</p>
                            @if (! empty($blackholeResult['rewrite_suggestion']))
                                <div class="eg-gm-blackhole-rewrite p-2 mb-2 rounded">
                                    <p class="small mb-2"><strong>{{ __('no_contact.blackhole_rewrite') }}:</strong> {{ $blackholeResult['rewrite_suggestion'] }}</p>
                                    <button type="button" class="btn btn-sm eg-gm-blackhole-btn" wire:click="destroyRewriteVersion" wire:loading.attr="disabled">
                                        {{ __('no_contact.blackhole_destroy_rewrite') }}
                                    </button>
                                </div>
                            @endif
                            <p class="fw-semibold mb-3">{{ $blackholeResult['closing_line'] ?? '' }}</p>
                            @if (! empty($blackholeResult['commitment_suggestion']))
                                <div class="eg-gm-alchemy-offer p-3 mb-3 rounded">
                                    <p class="small mb-2"><strong>{{ __('gamification.alchemy.offer_title') }}</strong></p>
                                    <p class="mb-2">«{{ $blackholeResult['commitment_suggestion'] }}»</p>
                                    <button type="button" class="btn btn-sm eg-gm-blackhole-btn me-2" wire:click="acceptAlchemyAndDestroy" wire:loading.attr="disabled">
                                        {{ __('gamification.alchemy.accept_destroy') }}
                                    </button>
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="eg-gm-blackhole-btn" wire:click="confirmDestroyBlackhole" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="confirmDestroyBlackhole,destroyRewriteVersion">{{ __('no_contact.blackhole_confirm_destroy') }}</span>
                                    <span wire:loading wire:target="confirmDestroyBlackhole,destroyRewriteVersion">…</span>
                                </button>
                                <button type="button" class="btn eg-btn-ghost" wire:click="clearBlackholeResult">{{ __('no_contact.blackhole_cancel') }}</button>
                            </div>
                        </div>
                    @else
                        <textarea
                            wire:model="blackholeDraft"
                            class="form-control eg-gm-blackhole-input mb-3"
                            rows="5"
                            placeholder="{{ __('no_contact.blackhole_placeholder') }}"
                        ></textarea>
                        @error('blackholeDraft')
                            <p class="text-danger small">{{ $message }}</p>
                        @enderror
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="eg-gm-blackhole-btn" wire:click="analyzeBlackhole" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="analyzeBlackhole">{{ __('no_contact.blackhole_analyze') }}</span>
                                <span wire:loading wire:target="analyzeBlackhole">…</span>
                            </button>
                            <button type="button" class="btn eg-btn-ghost" wire:click="destroyBlackholeDirect" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="destroyBlackholeDirect">{{ __('no_contact.blackhole_destroy_direct') }}</span>
                                <span wire:loading wire:target="destroyBlackholeDirect">…</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($showSlipPunishmentPicker && $punishmentChoices !== [])
                    @include('livewire.no-contact.partials.slip-punishment-picker')
                @elseif ($showSlipForm)
                    <div class="eg-gm-slip-panel eg-glass mb-4">
                        <div class="eg-gm-slip-panel__head">
                            <span class="eg-gm-slip-panel__icon" aria-hidden="true">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <div>
                                <h3 class="eg-gm-slip-panel__title">{{ __('no_contact.slip_confirm_title') }}</h3>
                                <p class="eg-gm-slip-panel__lead mb-0">{{ __('no_contact.slip_confirm_body', ['days' => eg_num($penaltyDays)]) }}</p>
                            </div>
                        </div>

                        <p class="eg-gm-slip-panel__label">{{ __('no_contact.slip_trigger_label') }}</p>
                        <div class="eg-gm-slip-triggers mb-3">
                            @foreach ($slipTriggers as $trigger)
                                @php
                                    $triggerIcon = match ($trigger) {
                                        'checked_profile' => 'fa-user',
                                        'sent_message' => 'fa-paper-plane',
                                        'felt_weak' => 'fa-battery-quarter',
                                        default => 'fa-ellipsis',
                                    };
                                @endphp
                                <label @class(['eg-gm-slip-trigger', 'eg-gm-slip-trigger--selected' => $slipTrigger === $trigger])>
                                    <input type="radio" wire:model.live="slipTrigger" value="{{ $trigger }}" name="slipTrigger" class="eg-gm-slip-trigger__input">
                                    <span class="eg-gm-slip-trigger__icon" aria-hidden="true">
                                        <i class="fa-solid {{ $triggerIcon }}"></i>
                                    </span>
                                    <span class="eg-gm-slip-trigger__text">{{ __('no_contact.slip_trigger_'.$trigger) }}</span>
                                    <span class="eg-gm-slip-trigger__check" aria-hidden="true">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('slipTrigger')
                            <p class="text-danger small mb-3">{{ $message }}</p>
                        @enderror

                        @if ($slipPreview !== [])
                            <div class="eg-gm-slip-preview mb-3">
                                <p class="eg-gm-slip-panel__label mb-2">{{ __('gamification.slip_preview.title') }}</p>
                                <ul class="eg-gm-slip-preview__list mb-2">
                                    @foreach ($slipPreview as $rule)
                                        <li wire:key="preview-{{ $rule['key'] ?? $loop->index }}">
                                            <strong>{{ $rule['name'] ?? '' }}</strong>
                                            @if (is_array($rule['effects'] ?? null))
                                                —
                                                @if (($rule['effects']['points'] ?? 0) != 0)
                                                    {{ ($rule['effects']['points'] > 0 ? '+' : '') . eg_num($rule['effects']['points']) }} pts
                                                @endif
                                                @if (($rule['effects']['coins'] ?? 0) != 0)
                                                    · {{ ($rule['effects']['coins'] > 0 ? '+' : '') . eg_num($rule['effects']['coins']) }} coins
                                                @endif
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if (($slipNet['points'] ?? 0) !== 0 || ($slipNet['coins'] ?? 0) !== 0)
                                    <p class="eg-gm-slip-preview__net small mb-0">
                                        {{ __('gamification.slip_preview.net') }}:
                                        <strong>{{ ($slipNet['points'] > 0 ? '+' : '') . eg_num($slipNet['points']) }} pts</strong>,
                                        <strong>{{ ($slipNet['coins'] > 0 ? '+' : '') . eg_num($slipNet['coins']) }} coins</strong>
                                    </p>
                                @endif
                                @if ($streakFreezeCharges > 0)
                                    <p class="small mb-0 text-info">{{ __('gamification.slip_preview.freeze_note') }}</p>
                                @endif
                                @if ($hasSlipDiscountPerk ?? false)
                                    <p class="small mb-0 text-success">{{ __('gamification.slip_preview.discount_note') }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="eg-gm-slip-actions">
                            <button type="button" wire:click="recordSlip" class="eg-gm-shield-btn eg-gm-shield-btn--primary eg-transition" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="recordSlip">
                                    <i class="fa-solid fa-wrench me-2" aria-hidden="true"></i>
                                    {{ __('no_contact.slip_confirm_yes') }}
                                </span>
                                <span wire:loading wire:target="recordSlip">…</span>
                            </button>
                            <button type="button" wire:click="cancelSlipTriage" class="eg-gm-shield-btn eg-gm-shield-btn--ghost eg-transition">
                                {{ __('no_contact.slip_confirm_no') }}
                            </button>
                        </div>
                    </div>
                @else
                    <button type="button" wire:click="beginSlipTriage" class="eg-gm-shield-btn eg-gm-shield-btn--outline eg-transition mb-4">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        {{ __('no_contact.slip_button') }}
                    </button>
                @endif

                @if ($truthFlashes !== [])
                    <div class="eg-gm-truth eg-glass">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <h3 class="h6 mb-0">{{ __('no_contact.truth_title') }}</h3>
                            <span class="small eg-text-muted">{{ __('no_contact.truth_counter', ['current' => eg_num($truthFlashIndex + 1), 'total' => eg_num(count($truthFlashes))]) }}</span>
                        </div>
                        <p class="eg-text-muted small mb-3">{{ __('no_contact.truth_subtitle') }}</p>
                        <div class="eg-gm-truth-card">
                            <i class="fa-solid fa-lightbulb mb-2" aria-hidden="true"></i>
                            <p class="mb-0">{{ $truthFlashes[$truthFlashIndex] ?? '' }}</p>
                        </div>
                        @if ($truthFlashIndex < count($truthFlashes) - 1)
                            <button type="button" class="btn eg-btn-ghost eg-transition mt-3" wire:click="nextTruthFlash">
                                {{ __('no_contact.truth_next') }}
                                <i class="fa-solid fa-arrow-{{ $isRtl ? 'left' : 'right' }} ms-1" data-icon-directional></i>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
                    </div>

                    <aside class="eg-gm-dashboard__rail" aria-label="{{ __('no_contact.sidebar_aria') }}">
                        @include('livewire.no-contact.partials.activity-feed')
                    </aside>
                </div>
            </div>
        @else
            <div class="eg-gm-shell eg-gm-shell--narrow text-center">
                <header class="eg-gm-header mb-4">
                    <div class="eg-gm-icon eg-gm-icon--complete" aria-hidden="true">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <p class="eg-gm-kicker mb-2">{{ __('no_contact.page_title') }}</p>
                    <span class="eg-gm-status">{{ __('no_contact.completed_badge') }}</span>
                </header>

                <div class="eg-gm-card eg-glass">
                    <h2 class="h4 fw-semibold mb-2">{{ __('no_contact.completed_title') }}</h2>
                    <p class="eg-text-muted mb-4">
                        {{ __('no_contact.completed_subtitle', ['days' => eg_num($state['duration_days'])]) }}
                    </p>
                    <button type="button" wire:click="restartAfterComplete" class="eg-gm-activate-btn">
                        {{ __('no_contact.start_again') }}
                    </button>
                </div>
            </div>
        @endif
    </section>
</div>

@push('scripts')
<script>
(() => {
    let ghostTimerInterval = null;
    let ghostVisibilityHandler = null;

    const toPersianDigits = (value, locale) => {
        if (locale !== 'fa') {
            return value;
        }

        return value.replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[Number(d)]);
    };

    const formatDuration = (seconds, locale, pattern) => {
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        const d = toPersianDigits(String(days), locale);
        const h = toPersianDigits(String(hours), locale);
        const m = toPersianDigits(String(minutes), locale);

        return pattern
            .replace('{days}', d)
            .replace('{hours}', h)
            .replace('{minutes}', m);
    };

    const destroyGhostTimer = () => {
        if (ghostVisibilityHandler !== null) {
            document.removeEventListener('visibilitychange', ghostVisibilityHandler);
            ghostVisibilityHandler = null;
        }

        if (ghostTimerInterval !== null) {
            window.clearInterval(ghostTimerInterval);
            ghostTimerInterval = null;
        }
    };

    const initGhostTimer = () => {
        destroyGhostTimer();

        const root = document.getElementById('eg-ghost-mode-timer');
        if (!root) {
            return;
        }

        const locale = root.getAttribute('data-digit-locale') || 'en';
        const startedAt = root.getAttribute('data-ghost-started-at');
        const endsAt = root.getAttribute('data-ghost-ends-at');
        const serverNow = root.getAttribute('data-ghost-server-now');
        const durationPattern = root.getAttribute('data-duration-pattern') || '{days} days, {hours} hours, and {minutes} minutes';

        if (!startedAt || !endsAt || !serverNow) {
            return;
        }

        const startMs = Date.parse(startedAt);
        const endMs = Date.parse(endsAt);
        const serverMs = Date.parse(serverNow);

        if (Number.isNaN(startMs) || Number.isNaN(endMs) || Number.isNaN(serverMs)) {
            return;
        }

        const offsetMs = serverMs - Date.now();
        const pad2 = (n) => String(n).padStart(2, '0');

        const daysEl = root.querySelector('[data-ghost-part="days"]');
        const hoursEl = root.querySelector('[data-ghost-part="hours"]');
        const minutesEl = root.querySelector('[data-ghost-part="minutes"]');
        const secondsEl = root.querySelector('[data-ghost-part="seconds"]');
        const streakEl = root.querySelector('[data-ghost-metric="streak"]');
        const remainingEl = root.querySelector('[data-ghost-metric="remaining"]');

        const setPart = (el, value) => {
            if (! el || el.textContent === value) {
                return;
            }

            el.textContent = value;
        };

        let lastMetricMinute = -1;

        const tick = () => {
            if (document.hidden) {
                return;
            }

            const now = Date.now() + offsetMs;
            const elapsed = Math.max(0, Math.floor((now - startMs) / 1000));
            const remaining = Math.max(0, Math.floor((endMs - now) / 1000));

            const days = Math.floor(elapsed / 86400);
            const hours = Math.floor((elapsed % 86400) / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;

            setPart(daysEl, toPersianDigits(String(days), locale));
            setPart(hoursEl, toPersianDigits(pad2(hours), locale));
            setPart(minutesEl, toPersianDigits(pad2(minutes), locale));
            setPart(secondsEl, toPersianDigits(pad2(seconds), locale));

            const minuteBucket = Math.floor(elapsed / 60);
            if (minuteBucket !== lastMetricMinute) {
                lastMetricMinute = minuteBucket;
                const remainingBucket = Math.floor(remaining / 60);

                if (streakEl) {
                    setPart(streakEl, formatDuration(elapsed, locale, durationPattern));
                }

                if (remainingEl) {
                    setPart(remainingEl, formatDuration(remaining, locale, durationPattern));
                }
            }
        };

        ghostVisibilityHandler = () => {
            if (! document.hidden) {
                tick();
            }
        };

        document.addEventListener('visibilitychange', ghostVisibilityHandler);

        tick();
        ghostTimerInterval = window.setInterval(tick, 1000);
    };

    window.ghostBreathingRing = (wire) => ({
        phase: 'inhale',
        phaseLabel: '',
        beats: 0,
        misses: 0,
        completed: false,
        inhaleMs: 4000,
        exhaleMs: 6000,
        timer: null,
        labels: { inhale: @js(__('no_contact.breath_inhale')), exhale: @js(__('no_contact.breath_exhale')), tap: @js(__('no_contact.breath_tap')) },
        init() {
            this.phaseLabel = this.labels.tap;
            this.schedulePhase();
        },
        schedulePhase() {
            clearTimeout(this.timer);
            const ms = this.phase === 'inhale' ? this.inhaleMs : this.exhaleMs;
            this.phaseLabel = this.phase === 'inhale' ? this.labels.inhale : this.labels.exhale;
            this.timer = setTimeout(() => {
                this.phase = this.phase === 'inhale' ? 'exhale' : 'inhale';
                if (this.phase === 'inhale') {
                    this.beats++;
                    if (this.beats >= 6) {
                        this.finish(true);
                    }
                }
                this.schedulePhase();
            }, ms);
        },
        tap() {
            if (this.completed) {
                return;
            }

            this.misses = 0;
            this.phase = this.phase === 'inhale' ? 'inhale' : 'exhale';
            this.schedulePhase();
        },
        finish(success) {
            this.completed = true;
            clearTimeout(this.timer);
            this.phaseLabel = success ? @js(__('no_contact.breath_success')) : @js(__('no_contact.breath_done'));
            wire.reportBreathingSuccess(success && this.misses <= 2);
        },
    });

    window.ghostPanicVoice = () => ({
        recording: false,
        hasRecording: false,
        mediaRecorder: null,
        chunks: [],
        storageKey: 'egomap_panic_voice',
        init() {
            this.hasRecording = !!localStorage.getItem(this.storageKey);
        },
        async toggleRecord() {
            if (this.recording && this.mediaRecorder) {
                this.mediaRecorder.stop();
                this.recording = false;

                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.chunks = [];
                this.mediaRecorder = new MediaRecorder(stream);
                this.mediaRecorder.ondataavailable = (e) => this.chunks.push(e.data);
                this.mediaRecorder.onstop = () => {
                    const blob = new Blob(this.chunks, { type: 'audio/webm' });
                    const reader = new FileReader();
                    reader.onload = () => {
                        localStorage.setItem(this.storageKey, reader.result);
                        this.hasRecording = true;
                    };
                    reader.readAsDataURL(blob);
                    stream.getTracks().forEach((t) => t.stop());
                };
                this.mediaRecorder.start();
                this.recording = true;
            } catch (e) {
                console.warn('Voice record unavailable', e);
            }
        },
        play() {
            const data = localStorage.getItem(this.storageKey);
            if (!data) {
                return;
            }

            const audio = new Audio(data);
            audio.play();
        },
    });

    const bootGhostMode = () => {
        initGhostTimer();
    };

    bootGhostMode();

    document.addEventListener('livewire:navigated', bootGhostMode);

    document.addEventListener('livewire:init', () => {
        bootGhostMode();

        Livewire.hook('message.processed', () => {
            queueMicrotask(bootGhostMode);
        });

        Livewire.hook('morph.updated', ({ el }) => {
            if (el?.id === 'eg-ghost-mode-timer' || el?.querySelector?.('#eg-ghost-mode-timer')) {
                queueMicrotask(bootGhostMode);
            }
        });
    });
})();
</script>
@endpush
