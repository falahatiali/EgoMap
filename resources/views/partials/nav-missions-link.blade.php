@php
    use App\Services\Missions\MissionNavigationService;

    $missionNav = app(MissionNavigationService::class)->forUser(auth()->user());
    $variant = $variant ?? 'link';
@endphp

@if ($variant === 'button')
    <a
        href="{{ $missionNav['href'] }}"
        class="eg-nav-missions-btn eg-transition"
        wire:navigate
    >
        <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
        <span>{{ $missionNav['primary_label'] }}</span>
        @if ($missionNav['active_count'] > 0)
            <span class="eg-nav-missions-badge">{{ eg_num($missionNav['active_count']) }}</span>
        @endif
    </a>
@else
    <li class="nav-item eg-nav__item">
        <a
            href="{{ $missionNav['href'] }}"
            class="eg-nav-pill eg-nav-pill--missions eg-transition"
            wire:navigate
        >
            <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
            <span>{{ $missionNav['primary_label'] }}</span>
            @if ($missionNav['active_count'] > 0)
                <span class="eg-nav-missions-badge">{{ eg_num($missionNav['active_count']) }}</span>
            @endif
        </a>
    </li>
@endif
