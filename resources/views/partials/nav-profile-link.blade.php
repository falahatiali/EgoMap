@php
    $variant = $variant ?? 'default';
@endphp

@if (isset($navProfile) && $navProfile !== null)
    @if ($variant === 'protocol')
        <a
            href="{{ route('profile', ['locale' => app()->getLocale()]) }}"
            class="rh-nav__btn rh-nav__btn--ghost rh-nav__profile"
            wire:navigate
        >
            <span class="rh-nav__profile-avatar" aria-hidden="true">{{ $navProfile->initial }}</span>
            <span class="rh-nav__profile-name">{{ $navProfile->name }}</span>
            @if ($navProfile->planBadge)
                <span @class([
                    'eg-nav-plan-badge',
                    'eg-nav-plan-badge--'.$navProfile->planPeriod => $navProfile->planPeriod !== null,
                ])>
                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                    <span>{{ $navProfile->planBadge }}</span>
                </span>
            @endif
        </a>
    @elseif ($variant === 'guided')
        <a href="{{ route('profile') }}" class="eg-nav-profile-link eg-transition" wire:navigate>
            <span class="eg-nav-profile-avatar" aria-hidden="true">{{ $navProfile->initial }}</span>
            <span class="eg-nav-profile-name">{{ $navProfile->name }}</span>
            @if ($navProfile->planBadge)
                <span @class([
                    'eg-nav-plan-badge',
                    'eg-nav-plan-badge--'.$navProfile->planPeriod => $navProfile->planPeriod !== null,
                ])>
                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                    <span>{{ $navProfile->planBadge }}</span>
                </span>
            @endif
        </a>
    @else
        <a href="{{ route('profile') }}" class="eg-nav-profile-link eg-transition" wire:navigate>
            <span class="eg-nav-profile-avatar" aria-hidden="true">{{ $navProfile->initial }}</span>
            <span class="eg-nav-profile-name">{{ $navProfile->name }}</span>
            @if ($navProfile->planBadge)
                <span @class([
                    'eg-nav-plan-badge',
                    'eg-nav-plan-badge--'.$navProfile->planPeriod => $navProfile->planPeriod !== null,
                ])>
                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                    <span>{{ $navProfile->planBadge }}</span>
                </span>
            @endif
        </a>
    @endif
@endif
