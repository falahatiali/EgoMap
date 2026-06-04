<div class="eg-profile-page">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => $missionNav['href'],
                    'label' => __('nav.my_missions'),
                    'icon' => 'fa-bullseye',
                ],
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
                    'icon' => 'fa-ghost',
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
                    <div class="eg-profile-hero-actions">
                        <a href="{{ $missionNav['href'] }}" class="btn eg-btn-primary eg-profile-missions-hero-btn" wire:navigate>
                            <i class="fa-solid fa-bullseye me-2" aria-hidden="true"></i>
                            {{ __('missions.open_missions') }}
                        </a>
                        <a href="{{ route('profile.rewards') }}" class="btn eg-btn-rewards-dossier eg-transition" wire:navigate>
                            <i class="fa-solid fa-crown" aria-hidden="true"></i>
                            {{ __('profile.rewards_card_cta') }}
                        </a>
                        <a href="{{ $missionNav['catalog_href'] }}" class="btn eg-btn-mission-browse eg-transition" wire:navigate>
                            <i class="fa-solid fa-compass" aria-hidden="true"></i>
                            {{ __('missions.browse_missions') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container eg-profile-section">
        <a href="{{ route('profile.rewards') }}" class="eg-profile-rewards-teaser eg-glass text-decoration-none text-reset" wire:navigate>
            <div class="eg-profile-rewards-teaser__glow" aria-hidden="true"></div>
            <div class="eg-profile-rewards-teaser__icon" aria-hidden="true">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div class="eg-profile-rewards-teaser__body">
                <h2 class="h5 mb-1">{{ __('profile.rewards_card_title') }}</h2>
                <p class="eg-text-muted small mb-0">{{ __('profile.rewards_card_sub') }}</p>
                @if ($rewardsPreview)
                    <p class="eg-profile-rewards-teaser__stats small mb-0 mt-2">
                        <i class="fa-solid fa-star"></i> {{ eg_num($rewardsPreview['points'] ?? 0) }}
                        <span class="mx-2" aria-hidden="true">·</span>
                        <i class="fa-solid fa-coins"></i> {{ eg_num($rewardsPreview['coins'] ?? 0) }}
                        <span class="mx-2" aria-hidden="true">·</span>
                        <i class="fa-solid fa-fire"></i> {{ eg_num($rewardsPreview['streak_days'] ?? 0) }}
                    </p>
                @endif
            </div>
            <span class="eg-profile-rewards-teaser__cta">
                {{ __('profile.rewards_card_cta') }}
                <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
            </span>
        </a>
    </section>

    <section id="missions" class="container eg-profile-section eg-profile-missions-section">
        <div class="eg-profile-section-head">
            <div>
                <h2 class="eg-display h4 mb-2">{{ __('missions.profile_missions') }}</h2>
                <p class="eg-text-muted mb-0">{{ __('missions.profile_missions_sub') }}</p>
            </div>
            <a href="{{ $missionNav['catalog_href'] }}" class="btn eg-btn-mission-browse eg-btn-mission-browse--compact eg-transition" wire:navigate>
                <i class="fa-solid fa-compass" aria-hidden="true"></i>
                {{ __('missions.browse_missions') }}
            </a>
        </div>

        @if ($missionEnrollments->isNotEmpty())
            <div class="eg-profile-missions-grid">
                @foreach ($missionEnrollments as $enrollment)
                    @php
                        $snap = $enrollment->template_snapshot;
                        $missionTitle = $enrollment->title
                            ?: ($snap['title'][$locale] ?? $snap['title']['en'] ?? __('missions.untitled'));
                        $missionIcon = $enrollment->template?->icon ?? 'fa-dumbbell';
                    @endphp
                    <a
                        href="{{ route('missions.workspace', ['locale' => $locale, 'enrollment' => $enrollment->uuid]) }}"
                        class="eg-profile-mission-card eg-glass text-decoration-none text-reset"
                        wire:navigate
                    >
                        <div class="eg-profile-mission-card-top">
                            <span class="eg-profile-mission-card-icon" aria-hidden="true">
                                <i class="fa-solid {{ $missionIcon }}"></i>
                            </span>
                            <h3 class="eg-profile-mission-card-title">{{ $missionTitle }}</h3>
                        </div>
                        <span class="eg-profile-mission-card-cta">
                            {{ __('missions.continue_mission') }}
                            <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="eg-profile-missions-empty eg-glass">
                <span class="eg-profile-missions-empty-icon" aria-hidden="true">
                    <i class="fa-solid fa-bullseye"></i>
                </span>
                <h3 class="h5 mb-2">{{ __('missions.profile_missions_empty_title') }}</h3>
                <p class="eg-text-muted mb-4">{{ __('missions.profile_missions_empty_body') }}</p>
                <a href="{{ $missionNav['catalog_href'] }}" class="btn eg-btn-mission-browse eg-transition" wire:navigate>
                    <i class="fa-solid fa-compass" aria-hidden="true"></i>
                    {{ __('missions.browse_missions') }}
                </a>
            </div>
        @endif
    </section>

    @if ($showQuizHistory)
        @include('livewire.profile.partials.quiz-history')
    @endif

    <section class="container eg-profile-section">
        <div class="eg-profile-section-head mb-3">
            <div>
                <h2 class="eg-display h4 mb-1">{{ __('profile.security_title') }}</h2>
                <p class="eg-text-muted mb-0">{{ __('profile.security_subtitle') }}</p>
            </div>
        </div>

        <div class="eg-profile-security-card eg-glass">
            @if (session('profile_notice'))
                <div class="alert alert-success mb-3" role="alert">
                    {{ session('profile_notice') }}
                </div>
            @endif

            <form wire:submit="revokeOtherSessions" class="eg-profile-security-form">
                <p class="eg-text-muted small mb-3">{{ __('profile.revoke_sessions_body') }}</p>

                <div class="mb-3">
                    <label for="revoke-password" class="form-label">{{ __('auth.password') }}</label>
                    <input
                        id="revoke-password"
                        type="password"
                        wire:model="revokePassword"
                        class="form-control @error('revokePassword') is-invalid @enderror"
                        placeholder="{{ __('auth.password_placeholder') }}"
                        autocomplete="current-password"
                    >
                    @error('revokePassword')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-outline-danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="revokeOtherSessions">
                        <i class="fa-solid fa-right-from-bracket me-1"></i>
                        {{ __('profile.revoke_sessions_button') }}
                    </span>
                    <span wire:loading wire:target="revokeOtherSessions">
                        {{ __('profile.revoke_sessions_loading') }}
                    </span>
                </button>
            </form>
        </div>
    </section>

    {{-- Recovery journey --}}
    <section class="container eg-profile-section pb-5">
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

            @if (($journey['advanced_locked'] ?? false) && ! ($journey['show_tests'] ?? false))
                <div class="eg-glass p-4 text-center opacity-75">
                    <i class="fa-solid fa-lock mb-2 eg-text-muted"></i>
                    <h3 class="h6 fw-semibold mb-1">{{ __('recovery.advanced_locked_title') }}</h3>
                    <p class="eg-text-muted small mb-0">{{ __('recovery.advanced_locked_body') }}</p>
                </div>
            @endif
        @endif
    </section>

    @if (! $showQuizHistory && ! ($journey['needs_triage'] ?? false))
        <section class="container eg-profile-section pb-5">
            <div class="eg-profile-empty eg-glass">
                <i class="fa-solid fa-flask"></i>
                <h3 class="h5 mb-2">{{ __('profile.no_tests_title') }}</h3>
                <p class="mb-4">{{ __('profile.no_tests_body') }}</p>
                <a href="{{ route('quiz.start', ['slug' => 'reboot-protocol']) }}" class="btn eg-btn-primary" wire:navigate>
                    {{ __('landing.cta_step1') }}
                </a>
            </div>
        </section>
    @endif
</div>
