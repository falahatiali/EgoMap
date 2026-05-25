@extends('layouts.app')

@section('title', __('common.brand'))

@section('content')
    @php
        $primaryQuiz = $featuredQuizzes->first();
        $locale = app()->getLocale();
        $startCta = route('onboarding');
    @endphp

    @if (session('quiz_notice'))
        <div class="container pt-4">
            <div class="alert alert-warning border-0 eg-glass mb-0" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i>
                {{ session('quiz_notice') }}
            </div>
        </div>
    @endif

    <section class="eg-hero eg-hero--home eg-hero--minimal" id="start">
        <div class="container">
            <span class="eg-badge mb-4 eg-shadow-sm">
                <i class="fa-solid fa-house-heart"></i>
                <span data-i18n="home.hero_badge">{{ __('home.hero_badge') }}</span>
            </span>
            <h1 class="eg-display eg-hero-title mb-4" data-i18n="home.hero_title">{{ __('home.hero_title') }}</h1>
            <p class="eg-hero-sub eg-text-muted mb-0" data-i18n="home.hero_subtitle">{{ __('home.hero_subtitle') }}</p>

            <div class="eg-hero-cta-wrap">
                <a href="{{ $startCta }}" class="eg-btn-primary eg-btn-pulse eg-btn-hero eg-transition eg-shadow-glow eg-hover-lift" wire:navigate>
                    <i class="fa-solid fa-stethoscope"></i>
                    <span data-i18n="home.cta_start">{{ __('home.cta_start') }}</span>
                    <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                </a>
                <a href="#pain" class="eg-btn-ghost eg-transition small">
                    <span data-i18n="home.cta_learn">{{ __('home.cta_learn') }}</span>
                </a>
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor" id="pain">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eg-badge mb-3">
                    <i class="fa-solid fa-heart-crack"></i>
                    <span data-i18n="home.pain_badge">{{ __('home.pain_badge') }}</span>
                </span>
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.pain_title">{{ __('home.pain_title') }}</h2>
                <p class="eg-text-muted mx-auto mb-0" style="max-width: 46ch;" data-i18n="home.pain_subtitle">{{ __('home.pain_subtitle') }}</p>
            </div>

            <div class="eg-pain-grid">
                @foreach ([1, 2, 3] as $n)
                    <article class="eg-pain-card eg-transition">
                        <div class="eg-pain-icon" aria-hidden="true">
                            <i class="fa-solid fa-{{ $n === 1 ? 'mask' : ($n === 2 ? 'bolt' : 'shield-halved') }}"></i>
                        </div>
                        <h3 class="h5 fw-semibold mb-2" data-i18n="home.pain_{{ $n }}_title">{{ __("home.pain_{$n}_title") }}</h3>
                        <p class="eg-text-muted small mb-0" data-i18n="home.pain_{{ $n }}_desc">{{ __("home.pain_{$n}_desc") }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor eg-whitespace-section" id="framework">
        <div class="container">
            <div class="text-center mb-5 pb-2">
                <span class="eg-badge mb-3">
                    <i class="fa-solid fa-route"></i>
                    <span data-i18n="home.framework_badge">{{ __('home.framework_badge') }}</span>
                </span>
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.framework_title">{{ __('home.framework_title') }}</h2>
                <p class="eg-text-muted mx-auto" style="max-width: 44ch;" data-i18n="home.framework_subtitle">{{ __('home.framework_subtitle') }}</p>
            </div>

            <div class="eg-framework-track">
                @foreach ([1, 2, 3] as $step)
                    <article class="eg-framework-step">
                        <span class="eg-framework-num" aria-hidden="true">0{{ $step }}</span>
                        <span class="eg-framework-label" data-i18n="home.framework_{{ $step }}_label">{{ __("home.framework_{$step}_label") }}</span>
                        <h3 class="h5 fw-semibold mb-2" data-i18n="home.framework_{{ $step }}_title">{{ __("home.framework_{$step}_title") }}</h3>
                        <p class="eg-text-muted small mb-0" data-i18n="home.framework_{{ $step }}_desc">{{ __("home.framework_{$step}_desc") }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eg-badge mb-3">
                    <i class="fa-solid fa-toolbox"></i>
                    <span data-i18n="home.features_badge">{{ __('home.features_badge') }}</span>
                </span>
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.features_title">{{ __('home.features_title') }}</h2>
                <p class="eg-text-muted mx-auto mb-0" style="max-width: 44ch;" data-i18n="home.features_subtitle">{{ __('home.features_subtitle') }}</p>
            </div>

            <div class="eg-features-grid">
                @foreach ([
                    ['icon' => 'file-pdf', 'n' => 1, 'href' => null],
                    ['icon' => 'robot', 'n' => 2, 'href' => null],
                    ['icon' => 'hourglass-half', 'n' => 3, 'href' => route('no-contact')],
                    ['icon' => 'dumbbell', 'n' => 4, 'href' => null],
                ] as $feature)
                    <article class="eg-feature-card @if (! empty($feature['href'])) eg-feature-card--link @endif">
                        @if (! empty($feature['href']))
                            <a href="{{ $feature['href'] }}" class="eg-feature-card-link stretched-link" wire:navigate></a>
                        @endif
                        <div class="eg-feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-{{ $feature['icon'] }}"></i>
                        </div>
                        <h3 class="h6 fw-semibold mb-2" data-i18n="home.feature_{{ $feature['n'] }}_title">{{ __("home.feature_{$feature['n']}_title") }}</h3>
                        <p class="eg-text-muted small mb-0" data-i18n="home.feature_{{ $feature['n'] }}_desc">{{ __("home.feature_{$feature['n']}_desc") }}</p>
                    </article>
                @endforeach
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

    <section class="eg-section eg-section-anchor" id="home">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eg-badge mb-3">
                    <i class="fa-solid fa-door-open"></i>
                    <span data-i18n="home.home_badge">{{ __('home.home_badge') }}</span>
                </span>
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.home_title">{{ __('home.home_title') }}</h2>
                <p class="eg-text-muted mx-auto mb-0" style="max-width: 46ch;" data-i18n="home.home_subtitle">{{ __('home.home_subtitle') }}</p>
            </div>

            <div class="eg-home-grid">
                @foreach ([
                    ['icon' => 'user-secret', 'n' => 1],
                    ['icon' => 'brain', 'n' => 2],
                    ['icon' => 'moon', 'n' => 3],
                ] as $pillar)
                    <article class="eg-home-card eg-transition">
                        <div class="eg-trust-icon mb-3"><i class="fa-solid fa-{{ $pillar['icon'] }}"></i></div>
                        <h3 class="h6 fw-semibold mb-2" data-i18n="home.home_{{ $pillar['n'] }}_title">{{ __("home.home_{$pillar['n']}_title") }}</h3>
                        <p class="eg-text-muted small mb-0" data-i18n="home.home_{{ $pillar['n'] }}_desc">{{ __("home.home_{$pillar['n']}_desc") }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor" id="pricing">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eg-badge mb-3">
                    <i class="fa-solid fa-layer-group"></i>
                    <span data-i18n="home.pricing_badge">{{ __('home.pricing_badge') }}</span>
                </span>
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.pricing_title">{{ __('home.pricing_title') }}</h2>
                <p class="eg-text-muted mx-auto mb-0" style="max-width: 44ch;" data-i18n="home.pricing_subtitle">{{ __('home.pricing_subtitle') }}</p>
            </div>

            <div class="eg-pricing-grid">
                <article class="eg-pricing-card">
                    <h3 class="h4 fw-semibold mb-1" data-i18n="home.pricing_free_name">{{ __('home.pricing_free_name') }}</h3>
                    <p class="eg-pricing-price mb-0" data-i18n="home.pricing_free_price">{{ __('home.pricing_free_price') }}</p>
                    <p class="eg-text-muted small mb-4" data-i18n="home.pricing_free_desc">{{ __('home.pricing_free_desc') }}</p>
                    <ul class="eg-pricing-list">
                        @foreach (range(1, 4) as $n)
                            <li>
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                <span data-i18n="home.pricing_free_{{ $n }}">{{ __("home.pricing_free_{$n}") }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ $startCta }}" class="eg-btn-ghost eg-transition w-100 text-center">
                        <span data-i18n="home.pricing_cta_free">{{ __('home.pricing_cta_free') }}</span>
                    </a>
                </article>

                <article class="eg-pricing-card eg-pricing-card--pro">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h3 class="h4 fw-semibold mb-0" data-i18n="home.pricing_pro_name">{{ __('home.pricing_pro_name') }}</h3>
                        <span class="eg-badge mb-0">
                            <span data-i18n="home.pricing_pro_badge">{{ __('home.pricing_pro_badge') }}</span>
                        </span>
                    </div>
                    <p class="eg-pricing-price mb-0" data-i18n="home.pricing_pro_price">{{ __('home.pricing_pro_price') }}</p>
                    <p class="eg-text-muted small mb-4" data-i18n="home.pricing_pro_desc">{{ __('home.pricing_pro_desc') }}</p>
                    <ul class="eg-pricing-list">
                        @foreach (range(1, 5) as $n)
                            <li>
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                <span data-i18n="home.pricing_pro_{{ $n }}">{{ __("home.pricing_pro_{$n}") }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ $startCta }}" class="eg-btn-primary eg-transition w-100 text-center">
                        <span data-i18n="home.pricing_cta_pro">{{ __('home.pricing_cta_pro') }}</span>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <section class="eg-section eg-section-anchor pb-5" id="roadmap">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eg-badge mb-3">
                    <i class="fa-solid fa-map"></i>
                    <span data-i18n="home.roadmap_badge">{{ __('home.roadmap_badge') }}</span>
                </span>
                <h2 class="eg-display eg-section-title mb-3" data-i18n="home.roadmap_title">{{ __('home.roadmap_title') }}</h2>
                <p class="eg-text-muted mx-auto mb-0" style="max-width: 44ch;" data-i18n="home.roadmap_subtitle">{{ __('home.roadmap_subtitle') }}</p>
            </div>

            <div class="eg-roadmap-list">
                @foreach (range(1, 4) as $n)
                    <article class="eg-roadmap-item">
                        <div class="flex-grow-1">
                            <h3 class="h6 fw-semibold mb-1" data-i18n="home.roadmap_{{ $n }}_title">{{ __("home.roadmap_{$n}_title") }}</h3>
                            <p class="eg-text-muted small mb-0" data-i18n="home.roadmap_{{ $n }}_desc">{{ __("home.roadmap_{$n}_desc") }}</p>
                        </div>
                        <span class="eg-roadmap-status" data-i18n="home.roadmap_{{ $n }}_status">{{ __("home.roadmap_{$n}_status") }}</span>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="eg-section pb-5">
        <div class="container text-center">
            <h2 class="eg-display eg-section-title mb-3" data-i18n="home.final_title">{{ __('home.final_title') }}</h2>
            <p class="eg-text-muted mx-auto mb-5" style="max-width: 44ch;" data-i18n="home.final_subtitle">{{ __('home.final_subtitle') }}</p>
            @if ($primaryQuiz)
                <a href="{{ $startCta }}" class="eg-btn-primary eg-btn-pulse eg-transition eg-shadow-glow d-inline-flex">
                    <span data-i18n="home.final_cta">{{ __('home.final_cta') }}</span>
                    <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                </a>
            @endif
        </div>
    </section>
@endsection

@section('sticky_cta')
    <a href="{{ route('onboarding') }}" class="eg-btn-primary eg-btn-pulse eg-transition w-100" wire:navigate>
        <i class="fa-solid fa-stethoscope"></i>
        <span data-i18n="home.cta_start">{{ __('home.cta_start') }}</span>
        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
    </a>
@endsection
