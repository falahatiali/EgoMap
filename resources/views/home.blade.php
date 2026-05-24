@extends('layouts.app')

@section('title', __('common.brand'))

@section('content')
    <section class="eg-hero" id="start">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="eg-badge mb-4 eg-shadow-sm">
                        <i class="fa-solid fa-sparkles"></i>
                        <span data-i18n="home.hero_badge">{{ __('home.hero_badge') }}</span>
                    </span>
                    <h1 class="eg-display eg-hero-title mb-4" data-i18n="home.hero_title">{{ __('home.hero_title') }}</h1>
                    <p class="eg-hero-sub eg-text-muted mb-5" data-i18n="home.hero_subtitle">{{ __('home.hero_subtitle') }}</p>
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <a href="#start" class="eg-btn-primary eg-transition eg-shadow-glow eg-hover-lift">
                            <span data-i18n="home.cta_start">{{ __('home.cta_start') }}</span>
                            <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                        </a>
                        <a href="#how-it-works" class="eg-btn-ghost eg-transition">
                            <span data-i18n="home.cta_learn">{{ __('home.cta_learn') }}</span>
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="eg-glass eg-shadow-md eg-transition p-4 p-xl-5 text-center">
                        <div class="mb-4">
                            <i class="fa-solid fa-heart-pulse fa-3x" style="color: var(--eg-accent-bright); opacity: 0.9;"></i>
                        </div>
                        <p class="eg-display fs-4 mb-2" data-i18n="home.report_type_value">{{ __('home.report_type_value') }}</p>
                        <p class="eg-text-muted small mb-0" data-i18n="home.report_type_label">{{ __('home.report_type_label') }}</p>
                        <hr class="border-secondary border-opacity-25 my-4">
                        <div class="d-flex justify-content-center gap-4 eg-text-muted small">
                            <span>
                                <i class="fa-solid fa-shield-halved me-1"></i>
                                <span data-i18n="home.trust_anonymous">{{ __('home.trust_anonymous') }}</span>
                            </span>
                            <span>
                                <i class="fa-solid fa-clock me-1"></i>
                                <span data-i18n="home.trust_fast">{{ __('home.trust_fast') }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="eg-trust-scroll">
                <div class="eg-glass eg-shadow-sm eg-transition eg-trust-card">
                    <div class="eg-trust-icon"><i class="fa-solid fa-user-secret"></i></div>
                    <h3 class="h6 fw-semibold mb-2" data-i18n="home.trust_anonymous">{{ __('home.trust_anonymous') }}</h3>
                    <p class="eg-text-muted small mb-0" data-i18n="home.trust_anonymous_desc">{{ __('home.trust_anonymous_desc') }}</p>
                </div>
                <div class="eg-glass eg-shadow-sm eg-transition eg-trust-card">
                    <div class="eg-trust-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <h3 class="h6 fw-semibold mb-2" data-i18n="home.trust_ai">{{ __('home.trust_ai') }}</h3>
                    <p class="eg-text-muted small mb-0" data-i18n="home.trust_ai_desc">{{ __('home.trust_ai_desc') }}</p>
                </div>
                <div class="eg-glass eg-shadow-sm eg-transition eg-trust-card">
                    <div class="eg-trust-icon"><i class="fa-solid fa-bolt"></i></div>
                    <h3 class="h6 fw-semibold mb-2" data-i18n="home.trust_fast">{{ __('home.trust_fast') }}</h3>
                    <p class="eg-text-muted small mb-0" data-i18n="home.trust_fast_desc">{{ __('home.trust_fast_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor eg-whitespace-section" id="how-it-works">
        <div class="container">
            <div class="text-center mb-5 pb-2">
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.steps_title">{{ __('home.steps_title') }}</h2>
                <p class="eg-text-muted mx-auto" style="max-width: 36ch;" data-i18n="home.steps_subtitle">{{ __('home.steps_subtitle') }}</p>
            </div>
            <div class="row g-4">
                @foreach ([1, 2, 3] as $step)
                    <div class="col-md-4">
                        <div class="eg-glass eg-shadow-sm eg-transition h-100 p-4">
                            <div class="eg-step-num">{{ $step }}</div>
                            <h3 class="h5 fw-semibold mb-2" data-i18n="home.step_{{ $step }}_title">{{ __("home.step_{$step}_title") }}</h3>
                            <p class="eg-text-muted small mb-0" data-i18n="home.step_{{ $step }}_desc">{{ __("home.step_{$step}_desc") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor" id="report-preview">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.report_title">{{ __('home.report_title') }}</h2>
                <p class="eg-text-muted" data-i18n="home.report_subtitle">{{ __('home.report_subtitle') }}</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="eg-glass eg-shadow-lg eg-report-card">
                        <p class="eg-text-muted small text-uppercase fw-semibold mb-2" style="letter-spacing: 0.08em;" data-i18n="home.report_type_label">{{ __('home.report_type_label') }}</p>
                        <p class="eg-type-pill mb-4" data-i18n="home.report_type_value">{{ __('home.report_type_value') }}</p>

                        <h3 class="h6 fw-semibold mb-3" data-i18n="home.report_strengths_title">{{ __('home.report_strengths_title') }}</h3>
                        <div class="mb-3">
                            @foreach ([1, 2, 3] as $n)
                                <div class="eg-strength-item">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="small" data-i18n="home.report_strength_{{ $n }}">{{ __("home.report_strength_{$n}") }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="eg-blindspot">
                            <h3 class="h6 fw-semibold mb-2">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                <span data-i18n="home.report_blindspot_title">{{ __('home.report_blindspot_title') }}</span>
                            </h3>
                            <p class="small mb-0 eg-text-muted" data-i18n="home.report_blindspot_desc">{{ __('home.report_blindspot_desc') }}</p>
                        </div>

                        <x-access-gate permission="reports.section.traps">
                            <div class="eg-pro-unlocked mt-4 p-4 eg-glass">
                                <h3 class="h6 fw-semibold mb-2" data-i18n="home.report_pro_title">{{ __('home.report_pro_title') }}</h3>
                                <p class="small eg-text-muted mb-0" data-i18n="home.report_pro_teaser">{{ __('home.report_pro_teaser') }}</p>
                            </div>
                            <x-slot:denied>
                                <div class="eg-pro-locked">
                                    <div class="eg-pro-blur">
                                        <h3 class="h6 fw-semibold mb-2" data-i18n="home.report_pro_title">{{ __('home.report_pro_title') }}</h3>
                                        <p class="small eg-text-muted mb-0" data-i18n="home.report_pro_teaser">{{ __('home.report_pro_teaser') }}</p>
                                    </div>
                                    <div class="eg-pro-overlay">
                                        <span class="eg-lock-badge">
                                            <i class="fa-solid fa-lock"></i>
                                            <span data-i18n="home.report_pro_locked">{{ __('home.report_pro_locked') }}</span>
                                        </span>
                                        <a href="#start" class="eg-btn-ghost eg-transition btn-sm">
                                            <span data-i18n="home.report_pro_cta">{{ __('home.report_pro_cta') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </x-slot:denied>
                        </x-access-gate>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="eg-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="eg-glass eg-mission eg-shadow-md text-center text-lg-start">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-8">
                                <span class="eg-badge mb-3">
                                    <i class="fa-solid fa-gift"></i>
                                    <span data-i18n="home.mission_badge">{{ __('home.mission_badge') }}</span>
                                </span>
                                <h2 class="eg-display h3 mb-3" data-i18n="home.mission_title">{{ __('home.mission_title') }}</h2>
                                <p class="eg-text-muted mb-0" data-i18n="home.mission_desc">{{ __('home.mission_desc') }}</p>
                            </div>
                            <div class="col-lg-4 text-center text-lg-end">
                                <a href="#start" class="eg-btn-primary eg-transition eg-shadow-glow">
                                    <i class="fa-solid fa-play"></i>
                                    <span data-i18n="home.mission_cta">{{ __('home.mission_cta') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="eg-section pb-5">
        <div class="container text-center">
            <h2 class="eg-display eg-section-title mb-3" data-i18n="home.final_title">{{ __('home.final_title') }}</h2>
            <p class="eg-text-muted mx-auto mb-5" style="max-width: 40ch;" data-i18n="home.final_subtitle">{{ __('home.final_subtitle') }}</p>
            <a href="#start" class="eg-btn-primary eg-transition eg-shadow-glow d-none d-lg-inline-flex">
                <span data-i18n="home.final_cta">{{ __('home.final_cta') }}</span>
                <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
            </a>
        </div>
    </section>
@endsection

@section('sticky_cta')
    <a href="#start" class="eg-btn-primary eg-transition w-100">
        <span data-i18n="home.final_cta">{{ __('home.final_cta') }}</span>
        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
    </a>
@endsection
