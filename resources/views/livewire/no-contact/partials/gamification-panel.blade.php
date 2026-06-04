@php
    $earnedBadges = $wallet['badges'] ?? [];
    $ownedPerks = $wallet['perks'] ?? [];
    $badgeCount = count($earnedBadges);
    $xpPercent = (int) ($wallet['xp_percent'] ?? 0);
@endphp

@if ($pendingAlchemy ?? null)
    <div class="eg-gm-alchemy-pending eg-glass mb-3 p-3">
        <p class="small fw-semibold mb-1">{{ __('gamification.alchemy.pending_title') }}</p>
        <p class="mb-2">«{{ $pendingAlchemy['commitment'] }}»</p>
        <button type="button" class="eg-gm-shield-btn eg-gm-shield-btn--primary btn-sm" wire:click="completeAlchemyCommitment">
            {{ __('gamification.alchemy.complete') }}
        </button>
    </div>
@endif

<div class="eg-gm-gamification mb-0">
    <div class="eg-gm-stats-bar eg-glass">
        <div class="eg-gm-stats-bar__item" title="{{ __('gamification.stats.coins') }}">
            <i class="fa-solid fa-coins"></i>
            <span>{{ eg_num($wallet['coins'] ?? 0) }}</span>
        </div>
        <div class="eg-gm-stats-bar__item" title="{{ __('gamification.stats.points') }}">
            <i class="fa-solid fa-star"></i>
            <span>{{ eg_num($wallet['points'] ?? 0) }}</span>
        </div>
        <div class="eg-gm-stats-bar__item" title="{{ __('gamification.stats.streak') }}">
            <i class="fa-solid fa-fire"></i>
            <span>{{ eg_num($wallet['streak_days'] ?? 0) }}</span>
        </div>
        <button type="button" class="eg-gm-stats-bar__item eg-gm-stats-bar__btn" wire:click="openBadgeGallery" title="{{ __('gamification.sections.badges') }}">
            <i class="fa-solid fa-medal"></i>
            <span>{{ eg_num($badgeCount) }}</span>
        </button>
        <div class="eg-gm-stats-bar__level">
            <span class="small">{{ __('gamification.stats.level') }} {{ eg_num($wallet['level'] ?? 1) }}</span>
            <div class="eg-gm-xp-bar" role="progressbar" aria-valuenow="{{ $xpPercent }}" aria-valuemin="0" aria-valuemax="100">
                <span class="eg-gm-xp-bar__fill" style="width: {{ $xpPercent }}%"></span>
            </div>
            <span class="eg-gm-xp-bar__label">{{ eg_num($wallet['xp_progress'] ?? 0) }}/{{ eg_num($wallet['xp_needed'] ?? 100) }} XP</span>
        </div>
        <button type="button" class="eg-gm-stats-bar__shop-btn" wire:click="toggleShop">
            <i class="fa-solid fa-bag-shopping"></i>
            {{ __('gamification.sections.shop') }}
        </button>
    </div>

    @if ($streakFreezeCharges > 0)
        <p class="eg-gm-freeze-note small mb-2">
            <i class="fa-solid fa-snowflake"></i>
            {{ __('gamification.shop.streak_freeze_active', ['count' => eg_num($streakFreezeCharges)]) }}
        </p>
    @endif

    @if ($ownedPerks !== [])
        <div class="eg-gm-perk-row mb-3">
            @foreach ($ownedPerks as $perkSlug)
                @php
                    $perkMeta = $perkCatalog->get($perkSlug);
                @endphp
                <div class="eg-gm-perk-card eg-glass" wire:key="perk-{{ $perkSlug }}">
                    <div>
                        <strong>{{ $perkMeta?->name ?? $perkSlug }}</strong>
                        @if ($perkMeta?->description)
                            <p class="small mb-0 eg-text-muted">{{ $perkMeta->description }}</p>
                        @endif
                    </div>
                    @if ($perkSlug === 'free_shield_repair')
                        <button type="button" class="eg-gm-perk-use-btn" wire:click="promptConsumePerk('{{ $perkSlug }}')">
                            {{ __('gamification.perks.use_now') }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($showShop)
        <div class="eg-gm-shop-panel eg-glass mb-3">
            <div class="eg-gm-shop-panel__head">
                <h3 class="h6 mb-0">{{ __('gamification.sections.shop') }}</h3>
                <button type="button" class="eg-gm-toast__close" wire:click="toggleShop" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <ul class="eg-gm-shop-list mb-0">
                @foreach ($shopItems as $item)
                    <li class="eg-gm-shop-item" wire:key="shop-{{ $item['slug'] }}">
                        <div>
                            <strong><i class="fa-solid {{ $item['icon'] ?? 'fa-bag-shopping' }} me-1"></i> {{ $item['name'] }}</strong>
                            @if (! empty($item['description']))
                                <p class="small mb-0 eg-text-muted">{{ $item['description'] }}</p>
                            @endif
                        </div>
                        <button
                            type="button"
                            class="eg-gm-shop-buy"
                            wire:click="purchaseShopItem('{{ $item['slug'] }}')"
                            @disabled(($wallet['coins'] ?? 0) < ($item['cost_coins'] ?? 0))
                        >
                            {{ eg_num($item['cost_coins']) }} <i class="fa-solid fa-coins"></i>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>

@if ($showLevelUpToast)
    <div class="eg-gm-levelup-toast eg-glass mb-4" aria-live="polite">
        <div>
            <p class="mb-1 fw-semibold">{{ __('gamification.toast.level_up', ['level' => eg_num($levelUpLevel)]) }}</p>
            <p class="small mb-0 eg-text-muted">{{ $levelUpNarrative !== '' ? $levelUpNarrative : __('gamification.toast.level_up_body') }}</p>
        </div>
        <button type="button" class="eg-gm-toast__close" wire:click="dismissLevelUpToast">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

@if ($showProtocolCelebration)
    <div class="eg-gm-celebration-modal" role="dialog" aria-modal="true">
        <div class="eg-gm-celebration-modal__backdrop" wire:click="dismissProtocolCelebration"></div>
        <div class="eg-gm-celebration-modal__panel eg-glass">
            <div class="eg-gm-celebration-modal__icon"><i class="fa-solid fa-crown"></i></div>
            <h2 class="h4">{{ __('gamification.protocol.title') }}</h2>
            <p class="eg-text-muted">{{ __('gamification.protocol.body') }}</p>
            <button type="button" class="eg-gm-activate-btn" wire:click="dismissProtocolCelebration">
                {{ __('gamification.protocol.continue') }}
            </button>
        </div>
    </div>
@endif

@if ($showBadgeGallery)
    <div class="eg-gm-badge-modal" role="dialog" aria-modal="true">
        <div class="eg-gm-badge-modal__backdrop" wire:click="closeBadgeGallery"></div>
        <div class="eg-gm-badge-modal__panel eg-glass">
            <div class="eg-gm-badge-modal__head">
                <h2 class="h5 mb-0">{{ __('gamification.sections.badges') }}</h2>
                <button type="button" class="eg-gm-toast__close" wire:click="closeBadgeGallery">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="eg-gm-badge-grid">
                @foreach ($badgeCatalog as $badge)
                    @php
                        $earned = in_array($badge->slug, $earnedBadges, true);
                    @endphp
                    <div @class(['eg-gm-badge-card', 'eg-gm-badge-card--locked' => ! $earned]) wire:key="badge-{{ $badge->slug }}">
                        <span class="eg-gm-badge-card__icon"><i class="fa-solid {{ $badge->icon }}"></i></span>
                        <strong>{{ $badge->name }}</strong>
                        @if ($badge->description)
                            <p class="small mb-0">{{ $badge->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if ($confirmPerkSlug !== null)
    <div class="eg-gm-perk-modal" role="dialog" aria-modal="true">
        <div class="eg-gm-perk-modal__backdrop" wire:click="cancelConsumePerk"></div>
        <div class="eg-gm-perk-modal__panel eg-glass">
            <h3 class="h6">{{ __('gamification.perks.confirm_title') }}</h3>
            <p class="small eg-text-muted">{{ __('gamification.perks.confirm_body') }}</p>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn eg-btn-ghost" wire:click="cancelConsumePerk">{{ __('no_contact.cancel') }}</button>
                <button type="button" class="eg-gm-activate-btn" wire:click="consumePerk('{{ $confirmPerkSlug }}')">
                    {{ __('gamification.perks.use_now') }}
                </button>
            </div>
        </div>
    </div>
@endif
