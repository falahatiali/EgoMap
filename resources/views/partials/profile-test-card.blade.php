@php
    $locale = app()->getLocale();
@endphp

<div class="col-md-6 col-xl-4">
    <a href="{{ $record['detail_url'] }}" class="eg-profile-test-card eg-glass" wire:navigate>
        <header class="eg-profile-test-card-top">
            <div
                class="eg-profile-test-badge"
                style="--eg-test-accent: {{ $record['palette']['accent'] }}; --eg-test-glow: {{ $record['palette']['glow'] }};"
                @if (! empty($record['type_label']))
                    title="{{ $record['type_label'] }}"
                @endif
            >
                @if ($record['is_reboot_protocol'] ?? false)
                    <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                @elseif (! empty($record['type_code']))
                    {{ strtoupper($record['type_code']) }}
                @elseif ($record['is_in_progress'])
                    <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                @else
                    <i class="fa-solid fa-flask" aria-hidden="true"></i>
                @endif
            </div>
            <span @class([
                'eg-profile-status',
                'eg-profile-status--active' => $record['is_in_progress'],
                'eg-profile-status--done' => ! $record['is_in_progress'],
            ])>
                {{ $record['is_in_progress'] ? __('profile.status_in_progress') : __('profile.status_completed') }}
            </span>

            <button
                type="button"
                class="eg-profile-delete-btn"
                wire:click.prevent.stop="deleteSession('{{ $record['session']->uuid }}')"
                onclick="return confirm(@js(__('profile.delete_test_confirm')))"
                aria-label="{{ __('profile.delete_test') }}"
                title="{{ __('profile.delete_test') }}"
            >
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
            </button>
        </header>

        <div class="eg-profile-test-card-body">
            <h3 class="eg-profile-test-name">{{ $record['quiz_name'] }}</h3>

            @if (! empty($record['result_title']) && ! $record['is_in_progress'])
                <p class="eg-profile-test-result">{{ $record['result_title'] }}</p>
                @if (! empty($record['tagline']))
                    <p class="eg-profile-test-tagline">{{ $record['tagline'] }}</p>
                @endif
            @elseif ($record['is_in_progress'])
                <p class="eg-profile-test-result eg-text-muted">
                    {{ __('profile.progress_label', [
                        'percent' => eg_num($record['progress_percent']),
                        'current' => eg_num($record['current_question']),
                        'total' => eg_num($record['total_questions']),
                    ]) }}
                </p>
                <div class="eg-profile-progress">
                    <div class="eg-profile-progress-bar" style="width: {{ $record['progress_percent'] }}%"></div>
                </div>
            @endif
        </div>

        <footer class="eg-profile-test-meta">
            <span class="eg-profile-test-date">
                @if ($record['is_in_progress'])
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                    {{ __('profile.started_at', ['date' => $record['started_at_label'] ?? '—']) }}
                @else
                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    {{ __('profile.completed_at', ['date' => $record['completed_at_label'] ?? '—']) }}
                @endif
            </span>
            <span class="eg-profile-test-arrow" aria-hidden="true">
                <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
            </span>
        </footer>
    </a>
</div>
