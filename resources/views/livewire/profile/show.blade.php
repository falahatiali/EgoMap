<div class="eg-profile-page">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
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
                [
                    'href' => route('no-contact'),
                    'label' => __('nav.no_contact'),
                    'icon' => 'fa-hourglass-half',
                ],
            ],
        ])
    </section>

    {{-- Hero --}}
    <section class="eg-profile-hero">
        <div class="container">
            <div class="eg-profile-hero-card eg-glass">
                <div class="eg-profile-avatar" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div class="eg-profile-hero-body">
                    <span class="eg-badge mb-3">
                        <i class="fa-solid fa-user-check"></i>
                        {{ __('profile.member') }}
                    </span>
                    <h1 class="eg-display eg-profile-name mb-2">{{ $user->name }}</h1>
                    <p class="eg-profile-email mb-3">{{ $user->email }}</p>
                    <div class="eg-profile-meta">
                        <span>
                            <i class="fa-regular fa-calendar me-1"></i>
                            {{ __('profile.member_since', ['date' => $user->created_at->translatedFormat('M Y')]) }}
                        </span>
                        @if ($user->email_verified_at)
                            <span class="eg-profile-verified">
                                <i class="fa-solid fa-circle-check me-1"></i>
                                {{ __('profile.verified') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Recovery journey --}}
    <section class="container eg-profile-section">
        @if ($journey['needs_triage'])
            <div class="eg-profile-primary-tool eg-glass text-center mb-4">
                <h2 class="h5 fw-semibold mb-2">{{ __('recovery.needs_triage_title') }}</h2>
                <p class="eg-text-muted small mb-3">{{ __('recovery.needs_triage_body') }}</p>
                <a href="{{ route('onboarding') }}" class="eg-btn-primary eg-transition" wire:navigate>
                    {{ __('recovery.needs_triage_cta') }}
                </a>
            </div>
        @else
            <div class="eg-profile-section-head mb-3">
                <div>
                    <h2 class="eg-display h4 mb-1">{{ __('recovery.journey_title') }}</h2>
                    <p class="eg-text-muted mb-0">{{ __('recovery.journey_subtitle') }}</p>
                </div>
            </div>

            <div class="eg-journey-steps">
                @foreach ($journey['steps'] as $step)
                    <div @class([
                        'eg-journey-step',
                        'is-current' => $step['is_current'],
                        'is-locked' => ! $step['unlocked'],
                    ])>
                        @if ($step['unlocked'])
                            <i class="fa-solid fa-circle-check text-success mb-2"></i>
                        @else
                            <i class="fa-solid fa-lock mb-2 eg-text-muted"></i>
                        @endif
                        <span class="small fw-semibold d-block">{{ $step['label'] }}</span>
                        @if ($step['is_current'])
                            <span class="badge rounded-pill mt-1" style="background: var(--eg-accent-soft); color: var(--eg-accent-bright);">
                                {{ __('recovery.current_phase') }}
                            </span>
                        @elseif (! $step['unlocked'] && $step['lock_reason'])
                            <p class="eg-journey-step-lock mb-0">{{ $step['lock_reason'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($journey['primary_tool'])
                <article class="eg-profile-primary-tool">
                    <div class="d-flex align-items-start gap-3">
                        <div class="eg-triage-rec-icon flex-shrink-0 mb-0">
                            <i class="fa-solid fa-{{ $journey['primary_tool']['icon'] }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="h5 fw-semibold mb-2">{{ $journey['primary_tool']['title'] }}</h3>
                            <p class="eg-text-muted small mb-3">{{ $journey['primary_tool']['body'] }}</p>
                            <a href="{{ $journey['primary_tool']['url'] }}" class="eg-btn-primary eg-transition" wire:navigate>
                                {{ __('recovery.open_tool') }}
                                <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }} ms-1" data-icon-directional></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endif

            @if ($journey['show_ai_coach'])
                <div class="eg-glass p-4 mb-4 opacity-75">
                    <h3 class="h6 fw-semibold mb-1">{{ __('recovery.ai_coach_soon') }}</h3>
                    <p class="eg-text-muted small mb-0">{{ __('recovery.ai_coach_soon_body') }}</p>
                </div>
            @endif
        @endif
    </section>

    {{-- Stats --}}
    @if (! ($journey['needs_triage'] ?? true) && ($journey['show_tests'] ?? false))
    <section class="container eg-profile-stats-row">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="eg-profile-stat eg-glass">
                    <span class="eg-profile-stat-value">{{ $totalTests }}</span>
                    <span class="eg-profile-stat-label">{{ __('profile.stat_total') }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="eg-profile-stat eg-glass">
                    <span class="eg-profile-stat-value">{{ $totalInProgress }}</span>
                    <span class="eg-profile-stat-label">{{ __('profile.stat_in_progress') }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="eg-profile-stat eg-glass">
                    <span class="eg-profile-stat-value">{{ $totalCompleted }}</span>
                    <span class="eg-profile-stat-label">{{ __('profile.stat_completed') }}</span>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- My Tests --}}
    @if (! ($journey['needs_triage'] ?? true) && ($journey['show_tests'] ?? false))
    <section class="container eg-profile-section eg-profile-tests-section pb-5" id="my-tests">
        <div class="eg-profile-section-head">
            <div>
                <h2 class="eg-display h4 mb-1">{{ __('profile.my_tests_title') }}</h2>
                <p class="eg-text-muted mb-0">{{ __('profile.my_tests_subtitle') }}</p>
            </div>
            <a href="{{ route('quiz.start', 'mbti-personality') }}" class="eg-btn-primary btn-sm" wire:navigate>
                <i class="fa-solid fa-plus me-1"></i>
                {{ __('profile.take_new_test') }}
            </a>
        </div>

        <div class="eg-profile-filters" role="tablist">
            <button
                type="button"
                wire:click="setFilter('all')"
                @class(['eg-profile-filter', 'is-active' => $filter === 'all'])
            >
                {{ __('profile.filter_all') }}
                <span class="eg-profile-filter-count">{{ $totalTests }}</span>
            </button>
            <button
                type="button"
                wire:click="setFilter('in_progress')"
                @class(['eg-profile-filter', 'is-active' => $filter === 'in_progress'])
            >
                {{ __('profile.filter_in_progress') }}
                <span class="eg-profile-filter-count">{{ $totalInProgress }}</span>
            </button>
            <button
                type="button"
                wire:click="setFilter('completed')"
                @class(['eg-profile-filter', 'is-active' => $filter === 'completed'])
            >
                {{ __('profile.filter_completed') }}
                <span class="eg-profile-filter-count">{{ $totalCompleted }}</span>
            </button>
        </div>

        @if ($filteredRecords->isEmpty())
            <div class="eg-profile-empty eg-glass">
                <i class="fa-solid fa-flask"></i>
                <h3 class="h5 mb-2">{{ __('profile.no_tests_title') }}</h3>
                <p class="mb-4">{{ __('profile.no_tests_body') }}</p>
                <a href="{{ route('home') }}#tests" class="btn eg-btn-primary">{{ __('profile.browse_tests') }}</a>
            </div>
        @else
            <div class="row g-4">
                @foreach ($filteredRecords as $record)
                    @include('partials.profile-test-card', ['record' => $record])
                @endforeach
            </div>
        @endif
    </section>
    @endif
</div>
