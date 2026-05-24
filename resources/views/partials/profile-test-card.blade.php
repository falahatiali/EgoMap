@php
    use App\Enums\SessionStatus;
    use App\Support\MbtiTypePalette;

    $locale = app()->getLocale();
    $isInProgress = $session->status === SessionStatus::InProgress;
    $report = $session->result?->free_report ?? [];
    $typeCode = $report['type_code'] ?? null;
    $resultTitle = $report['title'] ?? null;
    $quizName = $session->quiz->getTranslation('name', $locale);
    $total = max($session->quiz->questions_count ?? 1, 1);
    $current = min($session->current_sort_order ?? 1, $total);
    $percent = $isInProgress ? (int) round(($current / $total) * 100) : 100;
    $palette = MbtiTypePalette::for(strtolower((string) ($typeCode ?? '')));
    $detailUrl = $isInProgress
        ? route('quiz.session', $session->uuid)
        : route('profile.test.show', $session->uuid);
@endphp

<div class="col-md-6 col-xl-4">
    <a href="{{ $detailUrl }}" class="eg-profile-test-card eg-glass" wire:navigate>
        <div class="eg-profile-test-card-top">
            <div
                class="eg-profile-test-badge"
                style="--eg-test-accent: {{ $palette['accent'] }}; --eg-test-glow: {{ $palette['glow'] }};"
            >
                @if ($typeCode)
                    {{ strtoupper($typeCode) }}
                @elseif ($isInProgress)
                    <i class="fa-solid fa-hourglass-half"></i>
                @else
                    <i class="fa-solid fa-flask"></i>
                @endif
            </div>
            <span @class([
                'eg-profile-status',
                'eg-profile-status--active' => $isInProgress,
                'eg-profile-status--done' => ! $isInProgress,
            ])>
                {{ $isInProgress ? __('profile.status_in_progress') : __('profile.status_completed') }}
            </span>
        </div>

        <h3 class="eg-profile-test-name">{{ $quizName }}</h3>

        @if ($resultTitle && ! $isInProgress)
            <p class="eg-profile-test-result">{{ $resultTitle }}</p>
        @elseif ($isInProgress)
            <p class="eg-profile-test-result eg-text-muted">{{ __('profile.progress_label', ['percent' => $percent, 'current' => $current, 'total' => $total]) }}</p>
            <div class="eg-profile-progress mt-2">
                <div class="eg-profile-progress-bar" style="width: {{ $percent }}%"></div>
            </div>
        @endif

        <div class="eg-profile-test-meta">
            <span>
                @if ($isInProgress)
                    <i class="fa-regular fa-clock me-1"></i>
                    {{ __('profile.started_at', ['date' => $session->started_at->diffForHumans()]) }}
                @else
                    <i class="fa-regular fa-calendar-check me-1"></i>
                    {{ __('profile.completed_at', ['date' => $session->completed_at?->translatedFormat('j M Y') ?? '—']) }}
                @endif
            </span>
            <span class="eg-profile-test-arrow" aria-hidden="true">
                <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
            </span>
        </div>
    </a>
</div>
