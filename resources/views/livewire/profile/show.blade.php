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

    {{-- Stats --}}
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

    {{-- My Tests --}}
    <section class="container eg-profile-section eg-profile-tests-section pb-5" id="my-tests">
        <div class="eg-profile-section-head">
            <div>
                <h2 class="eg-display h4 mb-1">{{ __('profile.my_tests_title') }}</h2>
                <p class="eg-text-muted mb-0">{{ __('profile.my_tests_subtitle') }}</p>
            </div>
            <a href="{{ route('home') }}#tests" class="eg-btn-primary btn-sm">
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

        @if ($filteredSessions->isEmpty())
            <div class="eg-profile-empty eg-glass">
                <i class="fa-solid fa-flask"></i>
                <h3 class="h5 mb-2">{{ __('profile.no_tests_title') }}</h3>
                <p class="mb-4">{{ __('profile.no_tests_body') }}</p>
                <a href="{{ route('home') }}#tests" class="btn eg-btn-primary">{{ __('profile.browse_tests') }}</a>
            </div>
        @else
            <div class="row g-4">
                @foreach ($filteredSessions as $session)
                    @include('partials.profile-test-card', ['session' => $session])
                @endforeach
            </div>
        @endif
    </section>
</div>
