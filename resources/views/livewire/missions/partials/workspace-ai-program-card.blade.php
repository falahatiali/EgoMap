@php
    $targetLabel = $target === 'workout' ? __('missions.ai_workout') : __('missions.ai_meal');
    $detailUrl = $program ? route('profile.program.show', ['uuid' => $program->uuid]) : $profileUrl;
@endphp

<div class="eg-mission-program-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <strong>{{ $targetLabel }}</strong>
                @if ($program)
                    <span class="eg-badge">{{ __('missions.ai_program_version', ['version' => eg_num($program->version)]) }}</span>
                @endif
            </div>
            <p class="small mb-0 eg-text-muted">
                {{ $target === 'workout' ? __('missions.ai_program_workout_ready') : __('missions.ai_program_meal_ready') }}
            </p>
            @if ($program && ! empty($summary))
                <p class="small mb-0 mt-2">{{ $summary }}</p>
            @endif
        </div>
        <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary" wire:navigate>
            {{ __('missions.ai_program_view_full') }}
        </a>
    </div>
</div>
