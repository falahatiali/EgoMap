@php
    $streakDays = (int) ($wallet['streak_days'] ?? 0);
@endphp

<section class="eg-gm-daily-hub eg-glass mb-4" aria-label="{{ __('no_contact.daily_hub_aria') }}">
    <div class="eg-gm-streak-hero mb-3">
        <p class="eg-gm-streak-hero__label mb-1">{{ __('no_contact.clean_streak_days') }}</p>
        <p class="eg-gm-streak-hero__value mb-0">{{ eg_num($streakDays) }}</p>
        <p class="eg-gm-streak-hero__quote mb-0 mt-2">{{ $dailyQuote }}</p>
    </div>

    <div class="eg-gm-daily-grid">
        <article class="eg-gm-daily-card">
            <h3 class="h6 mb-2">
                <i class="fa-solid fa-bullseye me-2" aria-hidden="true"></i>
                {{ __('no_contact.daily_mission_title') }}
            </h3>
            <p class="small eg-text-muted mb-3">{{ __('no_contact.daily_mission_body') }}</p>
            @if ($dailyMissionDone)
                <span class="eg-gm-daily-done">
                    <i class="fa-solid fa-circle-check me-1"></i>
                    {{ __('no_contact.daily_mission_done') }}
                </span>
            @else
                <button type="button" class="eg-gm-daily-btn" wire:click="completeDailyMission" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="completeDailyMission">{{ __('no_contact.daily_mission_cta') }}</span>
                    <span wire:loading wire:target="completeDailyMission">…</span>
                </button>
            @endif
        </article>

        <article class="eg-gm-daily-card">
            <h3 class="h6 mb-2">
                <i class="fa-solid fa-ban me-2" aria-hidden="true"></i>
                {{ __('no_contact.block_title') }}
            </h3>
            <p class="small eg-text-muted mb-3">{{ __('no_contact.block_body') }}</p>
            @if ($blockConfirmedToday)
                <span class="eg-gm-daily-done">
                    <i class="fa-solid fa-circle-check me-1"></i>
                    {{ __('no_contact.block_done') }}
                </span>
            @else
                <button type="button" class="eg-gm-daily-btn eg-gm-daily-btn--outline" wire:click="confirmBlockEx" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmBlockEx">{{ __('no_contact.block_cta') }}</span>
                    <span wire:loading wire:target="confirmBlockEx">…</span>
                </button>
            @endif
        </article>
    </div>

    <p class="eg-gm-panic-hint small mb-0 mt-3">
        <i class="fa-solid fa-heart-pulse me-1" aria-hidden="true"></i>
        {{ __('no_contact.panic_reward_hint') }}
    </p>
</section>
