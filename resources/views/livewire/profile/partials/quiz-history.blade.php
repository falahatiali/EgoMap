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

<section class="container eg-profile-section eg-profile-tests-section" id="my-tests">
    <div class="eg-profile-section-head">
        <div>
            <h2 class="eg-display h4 mb-1">{{ __('profile.my_tests_title') }}</h2>
            <p class="eg-text-muted mb-0">{{ __('profile.my_tests_subtitle') }}</p>
        </div>
        <a href="{{ route('quiz.start', ['slug' => 'reboot-protocol']) }}" class="eg-btn-primary btn-sm" wire:navigate>
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

    <div class="row g-4">
        @foreach ($filteredRecords as $record)
            @include('partials.profile-test-card', ['record' => $record])
        @endforeach
    </div>
</section>
