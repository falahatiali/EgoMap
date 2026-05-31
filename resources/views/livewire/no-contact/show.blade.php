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

    <section class="container pb-5">
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
            <div class="eg-gm-shell text-center">
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
            @php
                $elapsed = (int) ($state['elapsed_seconds'] ?? 0);
                $remaining = (int) ($state['remaining_seconds'] ?? 0);
                $elapsedDays = intdiv($elapsed, 86400);
                $elapsedHours = intdiv($elapsed % 86400, 3600);
                $elapsedMinutes = intdiv($elapsed % 3600, 60);
                $elapsedSeconds = $elapsed % 60;
                $day = $elapsedDays + 1;
                $totalDays = (int) ($state['duration_days'] ?? 90);
            @endphp

            <div
                class="eg-gm-shell"
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

                <div class="eg-gm-card eg-glass eg-gm-timer-card">
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

                    <p class="eg-gm-day-of mb-0">
                        {{ __('no_contact.day_of', ['day' => eg_num($day), 'total' => eg_num($totalDays)]) }}
                    </p>
                </div>

                <ul class="eg-gm-metrics">
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

                <button type="button" class="eg-gm-emergency-btn" data-ghost-emergency-open>
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    {{ __('no_contact.emergency_button') }}
                </button>

                @if ($confirmSlip)
                    <div class="eg-gm-slip-confirm eg-glass">
                        <p class="fw-semibold mb-1">{{ __('no_contact.slip_confirm_title') }}</p>
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
                    <button type="button" wire:click="recordSlip" class="eg-gm-reset-btn eg-transition">
                        <i class="fa-solid fa-rotate-left me-2"></i>
                        {{ __('no_contact.slip_button') }}
                    </button>
                @endif
            </div>
        @else
            <div class="eg-gm-shell text-center">
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

    let emergencyInterval = null;
    let emergencyDocumentBound = false;

    const initEmergency = () => {
        const storage = window.localStorage;
        const draftKey = 'ghostModeEmergencyDraft';
        const endsKey = 'ghostModeEmergencyEndsAt';

        const buildModal = () => {
        if (document.getElementById('eg-ghost-emergency-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'eg-ghost-emergency-modal';
        modal.className = 'eg-gm-modal';
        modal.innerHTML = `
            <div class="eg-gm-modal-backdrop" data-ghost-emergency-close></div>
            <div class="eg-gm-modal-card eg-glass" role="dialog" aria-modal="true">
                <div class="eg-gm-modal-head">
                    <h3 class="h5 mb-0">${@js(__('no_contact.emergency_title'))}</h3>
                    <button type="button" class="eg-gm-modal-x" data-ghost-emergency-close aria-label="${@js(__('no_contact.emergency_close'))}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <p class="eg-text-muted small mb-3">${@js(__('no_contact.emergency_body'))}</p>
                <textarea class="form-control eg-gm-modal-text" rows="5" placeholder="${@js(__('no_contact.emergency_placeholder'))}" data-ghost-emergency-text></textarea>
                <div class="eg-gm-modal-actions mt-3">
                    <button type="button" class="btn eg-gm-modal-timer" data-ghost-emergency-start>${@js(__('no_contact.emergency_start_timer'))}</button>
                    <span class="eg-gm-modal-countdown" data-ghost-emergency-countdown>20:00</span>
                    <button type="button" class="btn btn-outline-light ms-auto" data-ghost-emergency-close>${@js(__('no_contact.emergency_close'))}</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    };

    const openModal = () => {
        buildModal();
        const modal = document.getElementById('eg-ghost-emergency-modal');
        if (!modal) return;
        modal.classList.add('is-open');
        const textarea = modal.querySelector('[data-ghost-emergency-text]');
        if (textarea instanceof HTMLTextAreaElement) {
            textarea.value = storage.getItem(draftKey) || '';
            textarea.focus();
            textarea.oninput = () => storage.setItem(draftKey, textarea.value);
        }
    };

    const closeModal = () => {
        document.getElementById('eg-ghost-emergency-modal')?.classList.remove('is-open');
    };

        const renderEmergency = () => {
        const el = document.querySelector('[data-ghost-emergency-countdown]');
        if (!el) return;
        const ends = Number(storage.getItem(endsKey) || 0);
        if (!ends) {
            el.textContent = '20:00';
            return;
        }
        const remaining = Math.max(0, Math.floor((ends - Date.now()) / 1000));
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        el.textContent = `${m}:${s}`;
        if (remaining <= 0 && emergencyInterval) {
            clearInterval(emergencyInterval);
            emergencyInterval = null;
        }
    };

        document.querySelectorAll('[data-ghost-emergency-open]').forEach((btn) => {
            if (btn.dataset.ghostEmergencyBound === '1') {
                return;
            }

            btn.dataset.ghostEmergencyBound = '1';
            btn.addEventListener('click', () => {
                openModal();
                if (emergencyInterval) {
                    clearInterval(emergencyInterval);
                }
                emergencyInterval = setInterval(renderEmergency, 250);
                renderEmergency();
            });
        });

        if (! emergencyDocumentBound) {
            emergencyDocumentBound = true;
            document.addEventListener('click', (e) => {
                const t = e.target;
                if (!(t instanceof HTMLElement)) {
                    return;
                }
                if (t.closest('[data-ghost-emergency-close]')) {
                    closeModal();
                }
                if (t.closest('[data-ghost-emergency-start]')) {
                    storage.setItem(endsKey, String(Date.now() + 20 * 60 * 1000));
                    renderEmergency();
                }
            });
        }
    };

    const bootGhostMode = () => {
        initGhostTimer();
        initEmergency();
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
