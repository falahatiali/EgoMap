{{-- Hero only — included from landing.blade.php --}}
<section class="rh-hero" id="top">
    <div class="rh-hero__cinematic" aria-hidden="true"></div>
    <div class="rh-hero__aurora" aria-hidden="true"></div>
    <div class="rh-hero__grid">
        <div class="rh-hero__content rh-hero__content--stack">
            <p class="rh-kicker" data-i18n="landing.core_message">{{ __('landing.core_message') }}</p>
            <h1 class="rh-hero__title">
                <span class="rh-hero__title-line" data-i18n="landing.hero_title_1">{{ __('landing.hero_title_1') }}</span>
                <span class="rh-hero__title-line rh-hero__title-line--accent" data-i18n="landing.hero_title_2">{{ __('landing.hero_title_2') }}</span>
            </h1>
            <p class="rh-hero__typed" aria-live="polite">
                <span class="rh-hero__typed-prefix" data-i18n="landing.hero_typed_prefix">{{ __('landing.hero_typed_prefix') }}</span>
                <span
                    class="rh-hero__typed-word"
                    data-hero-typed
                    data-words='@json(__('landing.hero_typed_words'))'
                ></span><span class="rh-hero__typed-cursor" aria-hidden="true"></span>
            </p>
            <p class="rh-lead" data-i18n="landing.hero_subtitle">{{ __('landing.hero_subtitle') }}</p>

            <div class="rh-emotional-banner">
                <p class="rh-emotional-banner__text" data-i18n="landing.hero_emotional_line">{{ __('landing.hero_emotional_line') }}</p>
            </div>

            <div class="rh-actions">
                <div class="rh-actions__col">
                    <button type="button" class="rh-btn rh-btn--primary rh-btn--hero" wire:click="startCheckIn">
                        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                        <span data-i18n="landing.cta_step1">{{ __('landing.cta_step1') }}</span>
                    </button>
                </div>
                <div class="rh-actions__col rh-actions__col--emergency">
                    <a href="#emergency" class="rh-btn rh-btn--emergency-hero">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <span data-i18n="landing.cta_emergency">{{ __('landing.cta_emergency') }}</span>
                    </a>
                    <p class="rh-caption rh-caption--emergency" data-i18n="landing.cta_emergency_note">{{ __('landing.cta_emergency_note') }}</p>
                </div>
            </div>
            <p class="rh-caption" data-i18n="landing.cta_step1_note">{{ __('landing.cta_step1_note') }}</p>
        </div>

        <aside class="rh-diag" aria-label="{{ __('landing.panel_aria') }}">
            <article class="rh-diag__card">
                <header class="rh-diag__top">
                    <h2 class="rh-diag__title" data-i18n="landing.panel_scan_title">{{ __('landing.panel_scan_title') }}</h2>
                </header>

                <ul class="rh-diag__rows">
                    <li class="rh-diag__row">
                        <span class="rh-diag__row-label" data-i18n="landing.panel_current_state">{{ __('landing.panel_current_state') }}</span>
                        <span class="rh-diag__row-value is-warn">
                            <span data-i18n="landing.panel_current_state_value">{{ __('landing.panel_current_state_value') }}</span>
                            <i class="fa-solid fa-circle rh-diag__pulse" aria-hidden="true"></i>
                        </span>
                    </li>
                    <li class="rh-diag__row rh-diag__row--risk">
                        <div class="rh-diag__row-head">
                            <span class="rh-diag__row-label" data-i18n="landing.panel_main_risk">{{ __('landing.panel_main_risk') }}</span>
                            <span class="rh-diag__row-value is-danger" data-i18n="landing.panel_main_risk_value">{{ __('landing.panel_main_risk_value') }}</span>
                        </div>
                        <div class="rh-diag__bar rh-diag__bar--warn rh-diag__bar--scan rh-diag__bar--risk-level" role="presentation"><span></span></div>
                    </li>
                    <li class="rh-diag__row">
                        <span class="rh-diag__row-label" data-i18n="landing.panel_nc">{{ __('landing.panel_nc') }}</span>
                        <span class="rh-diag__row-value is-calm" data-i18n="landing.panel_nc_value">{{ __('landing.panel_nc_value') }}</span>
                    </li>
                    <li class="rh-diag__row rh-diag__row--meter">
                        <div class="rh-diag__row-head">
                            <span class="rh-diag__row-label" data-i18n="landing.panel_rebuild_index">{{ __('landing.panel_rebuild_index') }}</span>
                            <span class="rh-diag__row-value" data-i18n="landing.panel_rebuild_value">{{ __('landing.panel_rebuild_value') }}</span>
                        </div>
                        <div class="rh-diag__bar rh-diag__bar--scan rh-diag__bar--rebuild" role="presentation"><span></span></div>
                    </li>
                </ul>

                <footer class="rh-diag__footer">
                    <span data-i18n="landing.panel_action">{{ __('landing.panel_action') }}</span>
                    <strong data-i18n="landing.panel_action_value">{{ __('landing.panel_action_value') }}</strong>
                </footer>
            </article>

            <a href="#emergency" class="rh-diag__sos">
                <span class="rh-diag__sos-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-heart"></i>
                </span>
                <span class="rh-diag__sos-copy">
                    <strong data-i18n="landing.panel_emergency_title">{{ __('landing.panel_emergency_title') }}</strong>
                    <span data-i18n="landing.panel_emergency_line_1">{{ __('landing.panel_emergency_line_1') }}</span>
                    <span class="rh-diag__sos-wait" data-i18n="landing.panel_emergency_line_2">{{ __('landing.panel_emergency_line_2') }}</span>
                </span>
            </a>
        </aside>
    </div>
</section>
