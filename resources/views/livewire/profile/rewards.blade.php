@php
    $wallet = $data['wallet'] ?? [];
    $totals = $data['totals'] ?? [];
    $hasWallet = (bool) ($data['has_wallet'] ?? false);
    $xpPercent = (int) ($wallet['xp_percent'] ?? 0);
@endphp

<div class="eg-rewards-page">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('profile'),
                    'label' => __('profile.rewards_back_profile'),
                    'icon' => 'fa-user',
                ],
                [
                    'href' => route('no-contact'),
                    'label' => __('nav.no_contact'),
                    'icon' => 'fa-ghost',
                ],
            ],
        ])
    </section>

    <section class="eg-rewards-hero">
        <div class="container">
            <div class="eg-rewards-hero-card eg-glass">
                <div class="eg-rewards-hero-card__glow" aria-hidden="true"></div>
                <div class="eg-rewards-hero-card__content">
                    <span class="eg-rewards-kicker">
                        <i class="fa-solid fa-crown me-2" aria-hidden="true"></i>
                        {{ __('profile.rewards_kicker') }}
                    </span>
                    <h1 class="eg-display eg-rewards-title mb-2">{{ __('profile.rewards_title') }}</h1>
                    <p class="eg-rewards-subtitle mb-0">{{ __('profile.rewards_subtitle', ['name' => $user->name]) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="container eg-rewards-body pb-5">
        @if (! $hasWallet)
            <div class="eg-rewards-empty eg-glass text-center">
                <span class="eg-rewards-empty__icon" aria-hidden="true"><i class="fa-solid fa-ghost"></i></span>
                <h2 class="h4 mb-2">{{ __('profile.rewards_empty_title') }}</h2>
                <p class="eg-text-muted mb-4">{{ __('profile.rewards_empty_body') }}</p>
                <a href="{{ route('no-contact') }}" class="btn eg-btn-primary" wire:navigate>
                    {{ __('profile.rewards_empty_cta') }}
                </a>
            </div>
        @else
            <div class="eg-rewards-stats">
                <article class="eg-rewards-stat eg-glass">
                    <span class="eg-rewards-stat__icon eg-rewards-stat__icon--points"><i class="fa-solid fa-star"></i></span>
                    <span class="eg-rewards-stat__label">{{ __('gamification.stats.points') }}</span>
                    <strong class="eg-rewards-stat__value">{{ eg_num($wallet['points'] ?? 0) }}</strong>
                </article>
                <article class="eg-rewards-stat eg-glass">
                    <span class="eg-rewards-stat__icon eg-rewards-stat__icon--coins"><i class="fa-solid fa-coins"></i></span>
                    <span class="eg-rewards-stat__label">{{ __('gamification.stats.coins') }}</span>
                    <strong class="eg-rewards-stat__value">{{ eg_num($wallet['coins'] ?? 0) }}</strong>
                </article>
                <article class="eg-rewards-stat eg-glass">
                    <span class="eg-rewards-stat__icon eg-rewards-stat__icon--streak"><i class="fa-solid fa-fire"></i></span>
                    <span class="eg-rewards-stat__label">{{ __('gamification.stats.streak') }}</span>
                    <strong class="eg-rewards-stat__value">{{ eg_num($wallet['streak_days'] ?? 0) }}</strong>
                </article>
                <article class="eg-rewards-stat eg-glass">
                    <span class="eg-rewards-stat__icon eg-rewards-stat__icon--level"><i class="fa-solid fa-bolt"></i></span>
                    <span class="eg-rewards-stat__label">{{ __('gamification.stats.level') }} {{ eg_num($wallet['level'] ?? 1) }}</span>
                    <div class="eg-rewards-stat__xp">
                        <div class="eg-rewards-xp-bar" role="progressbar" aria-valuenow="{{ $xpPercent }}" aria-valuemin="0" aria-valuemax="100">
                            <span class="eg-rewards-xp-bar__fill" style="width: {{ $xpPercent }}%"></span>
                        </div>
                        <span class="small eg-text-muted">{{ eg_num($wallet['xp_progress'] ?? 0) }}/{{ eg_num($wallet['xp_needed'] ?? 100) }} XP</span>
                    </div>
                </article>
            </div>

            <div class="eg-rewards-summary eg-glass">
                <div class="eg-rewards-summary__item">
                    <span class="eg-rewards-summary__value">{{ eg_num($totals['transactions'] ?? 0) }}</span>
                    <span class="eg-rewards-summary__label">{{ __('profile.rewards_stat_ledger') }}</span>
                </div>
                <div class="eg-rewards-summary__item">
                    <span class="eg-rewards-summary__value eg-rewards-summary__value--pos">{{ eg_num($totals['rewards'] ?? 0) }}</span>
                    <span class="eg-rewards-summary__label">{{ __('profile.rewards_stat_rewards') }}</span>
                </div>
                <div class="eg-rewards-summary__item">
                    <span class="eg-rewards-summary__value eg-rewards-summary__value--neg">{{ eg_num($totals['penalties'] ?? 0) }}</span>
                    <span class="eg-rewards-summary__label">{{ __('profile.rewards_stat_penalties') }}</span>
                </div>
                <div class="eg-rewards-summary__item">
                    <span class="eg-rewards-summary__value">{{ eg_num($totals['punishments_completed'] ?? 0) }}</span>
                    <span class="eg-rewards-summary__label">{{ __('profile.rewards_stat_recovery_done') }}</span>
                </div>
            </div>

            <div class="eg-rewards-grid">
                <div class="eg-rewards-grid__main">
                    <section class="eg-rewards-panel eg-glass" aria-labelledby="eg-rewards-ledger-title">
                        <header class="eg-rewards-panel__head">
                            <h2 id="eg-rewards-ledger-title" class="h5 mb-0">
                                <i class="fa-solid fa-scroll me-2" aria-hidden="true"></i>
                                {{ __('profile.rewards_ledger_title') }}
                            </h2>
                            <p class="eg-text-muted small mb-0">{{ __('profile.rewards_ledger_sub') }}</p>
                        </header>
                        @if (($data['transactions'] ?? []) === [])
                            <p class="eg-text-muted small mb-0 py-3">{{ __('profile.rewards_ledger_empty') }}</p>
                        @else
                            <ul class="eg-rewards-ledger mb-0">
                                @foreach ($data['transactions'] as $tx)
                                    <li wire:key="ledger-{{ $tx['id'] }}" @class(['eg-rewards-ledger__row', 'eg-rewards-ledger__row--'.$tx['tone']])>
                                        <div class="eg-rewards-ledger__main">
                                            <strong>{{ $tx['event_label'] }}</strong>
                                            @if ($tx['rule_name'] ?? null)
                                                <span class="d-block small eg-text-muted">{{ $tx['rule_name'] }}</span>
                                            @endif
                                            <time class="d-block small eg-text-muted">{{ $tx['created_at_formatted'] ?? $tx['created_at_human'] }}</time>
                                        </div>
                                        <div class="eg-rewards-ledger__deltas">
                                            @if (($tx['points_delta'] ?? 0) !== 0)
                                                <span>{{ ($tx['points_delta'] > 0 ? '+' : '') . eg_num($tx['points_delta']) }} pts</span>
                                            @endif
                                            @if (($tx['coins_delta'] ?? 0) !== 0)
                                                <span><i class="fa-solid fa-coins"></i> {{ ($tx['coins_delta'] > 0 ? '+' : '') . eg_num($tx['coins_delta']) }}</span>
                                            @endif
                                            @if (($tx['xp_delta'] ?? 0) !== 0)
                                                <span>+{{ eg_num($tx['xp_delta']) }} XP</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    <section class="eg-rewards-panel eg-glass" aria-labelledby="eg-rewards-recovery-title">
                        <header class="eg-rewards-panel__head">
                            <h2 id="eg-rewards-recovery-title" class="h5 mb-0">
                                <i class="fa-solid fa-hand-holding-heart me-2" aria-hidden="true"></i>
                                {{ __('profile.rewards_recovery_title') }}
                            </h2>
                            <p class="eg-text-muted small mb-0">{{ __('profile.rewards_recovery_sub') }}</p>
                        </header>
                        @if (($data['punishments'] ?? []) === [])
                            <p class="eg-text-muted small mb-0 py-3">{{ __('profile.rewards_recovery_empty') }}</p>
                        @else
                            <ul class="eg-rewards-recovery-list mb-0">
                                @foreach ($data['punishments'] as $task)
                                    <li wire:key="recovery-{{ $task['id'] }}" class="eg-rewards-recovery-item">
                                        <div class="eg-rewards-recovery-item__icon" aria-hidden="true">
                                            @if (($task['type'] ?? '') === 'physical')
                                                <i class="fa-solid fa-dumbbell"></i>
                                            @elseif (($task['type'] ?? '') === 'writing')
                                                <i class="fa-solid fa-pen-nib"></i>
                                            @else
                                                <i class="fa-solid fa-brain"></i>
                                            @endif
                                        </div>
                                        <div class="eg-rewards-recovery-item__body">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <strong>{{ $task['title'] }}</strong>
                                                <span @class([
                                                    'eg-rewards-pill',
                                                    'eg-rewards-pill--pending' => ($task['status'] ?? '') === 'pending',
                                                    'eg-rewards-pill--done' => ($task['status'] ?? '') === 'completed',
                                                ])>{{ __('profile.rewards_status_'.$task['status']) }}</span>
                                            </div>
                                            @if ($task['description'] ?? null)
                                                <p class="small eg-text-muted mb-1">{{ $task['description'] }}</p>
                                            @endif
                                            <p class="small mb-0 eg-text-muted">
                                                {{ __('profile.rewards_assigned', ['when' => $task['assigned_at']]) }}
                                                @if (($task['points_recovered'] ?? 0) > 0)
                                                    · {{ __('profile.rewards_recovered', ['points' => eg_num($task['points_recovered']), 'coins' => eg_num($task['coins_recovered'] ?? 0)]) }}
                                                @endif
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                <aside class="eg-rewards-grid__rail">
                    <section class="eg-rewards-panel eg-glass" aria-labelledby="eg-rewards-badges-title">
                        <header class="eg-rewards-panel__head">
                            <h2 id="eg-rewards-badges-title" class="h6 mb-0">
                                <i class="fa-solid fa-medal me-2" aria-hidden="true"></i>
                                {{ __('profile.rewards_badges_title') }}
                            </h2>
                        </header>
                        <div class="eg-rewards-badge-grid">
                            @foreach ($data['badges'] as $badge)
                                <div
                                    wire:key="badge-{{ $badge['slug'] }}"
                                    @class(['eg-rewards-badge', 'eg-rewards-badge--earned' => $badge['earned']])
                                    title="{{ $badge['name'] }}"
                                >
                                    <i class="fa-solid {{ $badge['icon'] ?? 'fa-medal' }}" aria-hidden="true"></i>
                                    <span class="eg-rewards-badge__name">{{ $badge['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @if (($data['perks'] ?? []) !== [])
                        <section class="eg-rewards-panel eg-glass" aria-labelledby="eg-rewards-perks-title">
                            <header class="eg-rewards-panel__head">
                                <h2 id="eg-rewards-perks-title" class="h6 mb-0">
                                    <i class="fa-solid fa-gift me-2" aria-hidden="true"></i>
                                    {{ __('profile.rewards_perks_title') }}
                                </h2>
                            </header>
                            <ul class="eg-rewards-perk-list mb-0">
                                @foreach ($data['perks'] as $perk)
                                    <li wire:key="perk-{{ $perk['slug'] }}">
                                        <strong>{{ $perk['name'] }}</strong>
                                        @if ($perk['description'] ?? null)
                                            <span class="d-block small eg-text-muted">{{ $perk['description'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <a href="{{ route('no-contact') }}" class="eg-rewards-cta eg-glass" wire:navigate>
                        <i class="fa-solid fa-ghost" aria-hidden="true"></i>
                        <span>{{ __('profile.rewards_go_ghost') }}</span>
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                    </a>
                </aside>
            </div>
        @endif
    </section>
</div>
