@extends('layouts.app')

@section('title', __('common.brand'))

@section('content')
    @php
        $primaryQuiz = $featuredQuizzes->first();
        $locale = app()->getLocale();
    @endphp

    @if (session('quiz_notice'))
        <div class="container pt-4">
            <div class="alert alert-warning border-0 eg-glass mb-0" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i>
                {{ session('quiz_notice') }}
            </div>
        </div>
    @endif

    <section class="eg-hero" id="start">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eg-badge mb-4 eg-shadow-sm">
                        <i class="fa-solid fa-sparkles"></i>
                        <span data-i18n="home.hero_badge">{{ __('home.hero_badge') }}</span>
                    </span>
                    <h1 class="eg-display eg-hero-title mb-4" data-i18n="home.hero_title">{{ __('home.hero_title') }}</h1>
                    <p class="eg-hero-sub eg-text-muted mb-4" data-i18n="home.hero_subtitle">{{ __('home.hero_subtitle') }}</p>

                    @if ($primaryQuiz)
                        <p class="eg-text-muted small mb-4">
                            <i class="fa-solid fa-circle-check text-success me-1"></i>
                            <span data-i18n="home.hero_test_available">{{ __('home.hero_test_available') }}</span>
                        </p>
                    @endif

                    <div class="d-flex flex-column flex-sm-row gap-3">
                        @if ($primaryQuiz)
                            <a href="{{ route('quiz.start', $primaryQuiz->slug) }}" class="eg-btn-primary eg-transition eg-shadow-glow eg-hover-lift">
                                <i class="fa-solid fa-play"></i>
                                <span data-i18n="home.test_card_start">{{ __('home.test_card_start') }}</span>
                                <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                            </a>
                        @endif
                        <a href="#tests" class="eg-btn-ghost eg-transition">
                            <span data-i18n="home.cta_browse_tests">{{ __('home.cta_browse_tests') }}</span>
                        </a>
                        <a href="#how-it-works" class="eg-btn-ghost eg-transition d-none d-sm-inline-flex">
                            <span data-i18n="home.cta_learn">{{ __('home.cta_learn') }}</span>
                        </a>
                    </div>
                </div>

                <div class="col-lg-6">
                    @if ($primaryQuiz)
                        <div class="eg-hero-test-panel">
                            @include('partials.quiz-card', ['quiz' => $primaryQuiz, 'featured' => true])
                        </div>
                    @else
                        <div class="eg-glass eg-shadow-md eg-transition p-4 p-xl-5 text-center">
                            <p class="eg-text-muted mb-0" data-i18n="home.tests_empty">{{ __('home.tests_empty') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($featuredQuizzes->isNotEmpty())
        <section class="eg-section eg-section-anchor pb-5" id="tests">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="eg-badge mb-3">
                        <i class="fa-solid fa-flask"></i>
                        <span data-i18n="home.tests_section_badge">{{ __('home.tests_section_badge') }}</span>
                    </span>
                    <h2 class="eg-display eg-section-title mb-3" data-i18n="home.tests_section_title">{{ __('home.tests_section_title') }}</h2>
                    <p class="eg-text-muted mx-auto mb-0" style="max-width: 42ch;" data-i18n="home.tests_section_subtitle">{{ __('home.tests_section_subtitle') }}</p>
                </div>

                <div class="eg-tests-grid">
                    @foreach ($featuredQuizzes as $quiz)
                        @include('partials.quiz-card', ['quiz' => $quiz, 'featured' => $loop->first])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="pb-5">
        <div class="container">
            <div class="eg-trust-scroll">
                <div class="eg-glass eg-shadow-sm eg-transition eg-trust-card">
                    <div class="eg-trust-icon"><i class="fa-solid fa-user-secret"></i></div>
                    <h3 class="h6 fw-semibold mb-2" data-i18n="home.trust_anonymous">{{ __('home.trust_anonymous') }}</h3>
                    <p class="eg-text-muted small mb-0" data-i18n="home.trust_anonymous_desc">{{ __('home.trust_anonymous_desc') }}</p>
                </div>
                <div class="eg-glass eg-shadow-sm eg-transition eg-trust-card">
                    <div class="eg-trust-icon"><i class="fa-solid fa-chart-simple"></i></div>
                    <h3 class="h6 fw-semibold mb-2" data-i18n="home.trust_science">{{ __('home.trust_science') }}</h3>
                    <p class="eg-text-muted small mb-0" data-i18n="home.trust_science_desc">{{ __('home.trust_science_desc') }}</p>
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
                        <p class="eg-type-pill mb-4">ENTP</p>

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
                                        @if ($primaryQuiz)
                                            <a href="{{ route('quiz.start', $primaryQuiz->slug) }}" class="eg-btn-ghost eg-transition btn-sm">
                                                <span data-i18n="home.test_card_start">{{ __('home.test_card_start') }}</span>
                                            </a>
                                        @endif
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
                                    <i class="fa-solid fa-envelope"></i>
                                    <span data-i18n="home.mission_badge">{{ __('home.mission_badge') }}</span>
                                </span>
                                <h2 class="eg-display h3 mb-3" data-i18n="home.mission_title">{{ __('home.mission_title') }}</h2>
                                <p class="eg-text-muted mb-0" data-i18n="home.mission_desc">{{ __('home.mission_desc') }}</p>
                            </div>
                            <div class="col-lg-4 text-center text-lg-end">
                                @if ($primaryQuiz)
                                    <a href="{{ route('quiz.start', $primaryQuiz->slug) }}" class="eg-btn-primary eg-transition eg-shadow-glow">
                                        <i class="fa-solid fa-play"></i>
                                        <span data-i18n="home.test_card_start">{{ __('home.test_card_start') }}</span>
                                    </a>
                                @endif
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
            @if ($primaryQuiz)
                <a href="{{ route('quiz.start', $primaryQuiz->slug) }}" class="eg-btn-primary eg-transition eg-shadow-glow d-none d-lg-inline-flex">
                    <span data-i18n="home.final_cta">{{ __('home.final_cta') }}</span>
                    <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                </a>
            @endif
        </div>
    </section>
@endsection

@section('sticky_cta')
    @if ($primaryQuiz ?? null)
        <a href="{{ route('quiz.start', $primaryQuiz->slug) }}" class="eg-btn-primary eg-transition w-100">
            <i class="fa-solid fa-play"></i>
            <span data-i18n="home.test_card_start">{{ __('home.test_card_start') }}</span>
            <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
        </a>
    @endif
@endsection
