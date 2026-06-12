@php
    $targetLabel = $target === 'workout' ? __('missions.ai_workout') : __('missions.ai_meal');
    $detailUrl = $program ? route('profile.program.show', ['uuid' => $program->uuid]) : $profileUrl;
    $icon = $target === 'workout' ? 'fa-dumbbell' : 'fa-utensils';
@endphp

<div class="eg-aether-mission-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="d-flex gap-3 align-items-start">
            <span class="eg-aether-option__icon" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <strong class="h6 mb-0">{{ $targetLabel }}</strong>
                    @if ($program)
                        <span class="eg-badge">{{ __('missions.ai_program_version', ['version' => eg_num($program->version)]) }}</span>
                    @endif
                </div>
                <p class="small mb-0 eg-text-muted">
                    {{ $target === 'workout' ? __('missions.ai_program_workout_ready') : __('missions.ai_program_meal_ready') }}
                </p>
                @if ($program && ! empty($summary))
                    <p class="small mb-0 mt-2 fw-semibold" style="color:#a7f3d0;">{{ $summary }}</p>
                @endif
            </div>
        </div>
        <a href="{{ $detailUrl }}" class="btn btn-sm eg-aether-mission-card__cta" wire:navigate>
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>{{ __('missions.ai_program_view_full') }}
        </a>
    </div>
</div>
