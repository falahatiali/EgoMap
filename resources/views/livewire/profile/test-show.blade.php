@php
    $typeCode = $report['type_code'] ?? '—';
    $locale = app()->getLocale();
@endphp

<div
    class="eg-profile-page eg-profile-test-detail"
    style="--eg-result-accent: {{ $palette['accent'] }}; --eg-result-soft: {{ $palette['soft'] }}; --eg-result-glow: {{ $palette['glow'] }};"
>
    <section class="container container-xl pt-4">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('profile'),
                    'label' => __('profile.back_to_profile'),
                    'icon' => 'fa-arrow-left',
                    'directional' => true,
                    'wireNavigate' => true,
                ],
                [
                    'href' => route('home'),
                    'label' => __('quiz.back_home'),
                    'icon' => 'fa-house',
                ],
                [
                    'href' => route('home').'#tests',
                    'label' => __('profile.browse_tests'),
                    'icon' => 'fa-flask',
                ],
            ],
        ])
    </section>

    <section
        class="eg-profile-result-hero"
        style="--eg-result-accent: {{ $palette['accent'] }}; --eg-result-soft: {{ $palette['soft'] }}; --eg-result-glow: {{ $palette['glow'] }};"
    >
        <div class="container container-xl">
            <div class="eg-profile-result-hero-inner">
                <p class="eg-profile-result-eyebrow">{{ $quizName }}</p>
                <div class="eg-profile-result-type-badge">{{ strtoupper($typeCode) }}</div>
                <h1 class="eg-display eg-profile-result-title">{{ $report['title'] ?? '' }}</h1>
                <p class="eg-profile-result-summary">{{ $content['tagline'] ?? ($report['summary'] ?? '') }}</p>
                @if ($completedAt)
                    <p class="eg-profile-result-date mb-0">
                        <i class="fa-regular fa-calendar-check me-1"></i>
                        {{ __('profile.completed_at', ['date' => $completedAt->translatedFormat('j F Y')]) }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    <section class="container container-xl eg-profile-result-content pb-5">
        <div class="eg-profile-result-stack">
            @include('partials.quiz-result-details', [
                'report' => $report,
                'content' => $content,
                'theme' => 'dark',
            ])
        </div>

        <div class="eg-profile-result-actions">
            <a href="{{ route('quiz.start', $session->quiz->slug) }}" class="btn eg-btn-primary">
                <i class="fa-solid fa-rotate-right me-1"></i>
                {{ __('profile.retake') }}
            </a>
            <a href="{{ route('quiz.result', $session->uuid) }}" class="btn eg-btn-ghost">
                {{ __('profile.open_full_result') }}
            </a>
            <a href="{{ route('home') }}" class="btn eg-btn-ghost">
                <i class="fa-solid fa-house me-1"></i>
                {{ __('quiz.back_home') }}
            </a>
        </div>
    </section>
</div>
