@php
    $targetLabel = $target === 'workout' ? __('missions.ai_workout') : __('missions.ai_meal');
    $detailUrl = $program ? route('profile.program.show', ['uuid' => $program->uuid]) : $profileUrl;
    $icon = $target === 'workout' ? 'fa-dumbbell' : 'fa-utensils';
    $modifier = $target === 'workout' ? 'workout' : 'meal';
@endphp

<article class="eg-aether-plan-card eg-aether-plan-card--{{ $modifier }} eg-aether-plan-card--active">
    <div class="eg-aether-plan-card__orb eg-aether-plan-card__orb--{{ $modifier === 'workout' ? 'emerald' : 'indigo' }}" aria-hidden="true"></div>
    <div class="eg-aether-plan-card__content">
        <div class="eg-aether-plan-card__icon" aria-hidden="true">
            <i class="fa-solid {{ $icon }}"></i>
        </div>
        <div class="eg-aether-plan-card__copy">
            <div class="eg-aether-plan-card__meta">
                <h3 class="eg-aether-plan-card__title">{{ $targetLabel }}</h3>
                @if ($program)
                    <span class="eg-aether-plan-card__badge">
                        {{ __('missions.ai_program_version', ['version' => eg_num($program->version)]) }}
                    </span>
                @endif
                <span class="eg-aether-plan-card__status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    {{ $target === 'workout' ? __('missions.ai_program_workout_ready') : __('missions.ai_program_meal_ready') }}
                </span>
            </div>
            @if ($program && ! empty($summary))
                <p class="eg-aether-plan-card__summary">{{ $summary }}</p>
            @endif
            @if ($target === 'workout' && isset($adherencePercent) && $adherencePercent > 0)
                <div class="eg-aether-plan-card__adherence">
                    <span>{{ __('missions.mission_progress') }}</span>
                    <strong>{{ eg_num($adherencePercent) }}%</strong>
                    <div class="eg-aether-progress-bar" aria-hidden="true">
                        <span style="width: {{ min(100, $adherencePercent) }}%"></span>
                    </div>
                </div>
            @endif
        </div>
        <div class="eg-aether-plan-card__action">
            <a href="{{ $detailUrl }}" class="eg-aether-plan-card__cta" wire:navigate>
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                {{ __('missions.ai_program_view_full') }}
            </a>
        </div>
    </div>
</article>
