@props([
    'href',
    'icon',
    'label',
    'modifier' => null,
    'badge' => null,
    'dismiss' => true,
])

<a
    href="{{ $href }}"
    @class([
        'eg-nav-drawer-link eg-transition',
        'eg-nav-drawer-link--'.$modifier => $modifier !== null,
    ])
    @if ($dismiss)
        data-bs-dismiss="offcanvas"
        data-bs-target="#egNavDrawer"
    @endif
    wire:navigate
>
    <span class="eg-nav-drawer-link__icon" aria-hidden="true">
        <i class="fa-solid fa-{{ $icon }}"></i>
    </span>
    <span class="eg-nav-drawer-link__label">{{ $label }}</span>
    @if ($badge !== null && $badge !== '')
        <span class="eg-nav-drawer-link__badge">{{ $badge }}</span>
    @endif
    <i class="fa-solid fa-chevron-right eg-nav-drawer-link__chevron" aria-hidden="true"></i>
</a>
