@php
    $navLinks = [
        [
            'href' => route('home'),
            'label' => __('quiz.back_home'),
            'icon' => 'fa-house',
        ],
        [
            'href' => route('profile'),
            'label' => __('profile.page_title'),
            'icon' => 'fa-user',
            'wireNavigate' => true,
        ],
    ];
@endphp

<div
    class="container py-5 eg-quiz-returning"
    style="--eg-result-accent: {{ $palette['accent'] }}; --eg-result-soft: {{ $palette['soft'] }}; --eg-result-glow: {{ $palette['glow'] }};"
>
    <div class="mb-4">
        @include('partials.page-nav-actions', [
            'variant' => 'dark',
            'links' => $navLinks,
        ])
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="eg-quiz-card card border-0 shadow-lg">
                <div class="card-body p-4 p-md-5 text-center">
                    <p class="eg-quiz-returning-eyebrow">{{ __('quiz.returning_eyebrow') }}</p>
                    <p class="eg-quiz-returning-quiz-name">{{ $quizName }}</p>

                    <div class="eg-quiz-returning-badge">{{ strtoupper($typeCode) }}</div>
                    <h1 class="eg-quiz-returning-title">{{ $title }}</h1>
                    <p class="eg-quiz-returning-summary">{{ $summary }}</p>

                    @if ($completedAt)
                        <p class="eg-quiz-returning-date">
                            <i class="fa-regular fa-calendar-check me-1"></i>
                            {{ __('quiz.returning_completed_at', ['date' => $completedAt->translatedFormat('j F Y')]) }}
                        </p>
                    @endif

                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <a
                            href="{{ route('quiz.result', ['uuid' => $returningSession->uuid]) }}"
                            class="btn btn-lg eg-quiz-returning-primary"
                            wire:navigate
                        >
                            <i class="fa-solid fa-chart-pie me-1"></i>
                            {{ __('quiz.view_previous_result') }}
                        </a>
                        <button
                            type="button"
                            class="btn btn-lg eg-quiz-returning-secondary"
                            wire:click="startRetake"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="startRetake">
                                <i class="fa-solid fa-rotate-right me-1"></i>
                                {{ __('quiz.retake_test') }}
                            </span>
                            <span wire:loading wire:target="startRetake">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i>
                                {{ __('quiz.preparing') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('quiz-clear-stored-session', (event) => {
        const slug = event.detail?.slug;

        if (slug) {
            localStorage.removeItem(`egomap_quiz_${slug}`);
        }
    });
</script>
@endpush
