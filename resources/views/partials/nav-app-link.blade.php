@props([
    'href',
    'icon',
    'label',
    'modifier' => null,
    'mobileOnly' => false,
])

<li @class([
    'nav-item eg-nav__item',
    'd-xl-none' => $mobileOnly,
])>
    <a
        href="{{ $href }}"
        @class([
            'eg-nav-pill eg-transition',
            'eg-nav-pill--'.$modifier => $modifier !== null && $modifier !== 'mobile-only',
        ])
        wire:navigate
    >
        <i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i>
        <span>{{ $label }}</span>
    </a>
</li>
