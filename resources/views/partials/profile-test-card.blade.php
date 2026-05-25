@php
    $locale = app()->getLocale();
@endphp

<div class="col-md-6 col-xl-4">
    <a href="{{ $record['detail_url'] }}" class="eg-profile-test-card eg-glass" wire:navigate>
        <div class="eg-profile-test-card-top">
            <div
                class="eg-profile-test-badge"
                style="--eg-test-accent: {{ $record['palette']['accent'] }}; --eg-test-glow: {{ $record['palette']['glow'] }};"
            >
                @if (! empty($record['type_code']))
                    {{ strtoupper($record['type_code']) }}
                @elseif ($record['is_in_progress'])
                    <i class="fa-solid fa-hourglass-half"></i>
                @else
                    <i class="fa-solid fa-flask"></i>
                @endif
            </div>
            <span @class([
                'eg-profile-status',
                'eg-profile-status--active' => $record['is_in_progress'],
                'eg-profile-status--done' => ! $record['is_in_progress'],
            ])>
                {{ $record['is_in_progress'] ? __('profile.status_in_progress') : __('profile.status_completed') }}
            </span>
        </div>

        <h3 class="eg-profile-test-name">{{ $record['quiz_name'] }}</h3>

        @if (! empty($record['result_title']) && ! $record['is_in_progress'])
            <p class="eg-profile-test-result">{{ $record['result_title'] }}</p>
            @if (! empty($record['tagline']))
                <p class="eg-profile-test-tagline small mb-0">{{ $record['tagline'] }}</p>
            @endif
        @elseif ($record['is_in_progress'])
            <p class="eg-profile-test-result eg-text-muted">
                {{ __('profile.progress_label', [
                    'percent' => $record['progress_percent'],
                    'current' => $record['current_question'],
                    'total' => $record['total_questions'],
                ]) }}
            </p>
            <div class="eg-profile-progress mt-2">
                <div class="eg-profile-progress-bar" style="width: {{ $record['progress_percent'] }}%"></div>
            </div>
        @endif

        <div class="eg-profile-test-meta">
            <span>
                @if ($record['is_in_progress'])
                    <i class="fa-regular fa-clock me-1"></i>
                    {{ __('profile.started_at', ['date' => $record['started_at_label'] ?? '—']) }}
                @else
                    <i class="fa-regular fa-calendar-check me-1"></i>
                    {{ __('profile.completed_at', ['date' => $record['completed_at_label'] ?? '—']) }}
                @endif
            </span>
            <span class="eg-profile-test-arrow" aria-hidden="true">
                <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
            </span>
        </div>
    </a>
</div>
