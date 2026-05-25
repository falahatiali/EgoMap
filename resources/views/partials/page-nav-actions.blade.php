@php
    $variant = $variant ?? 'dark';
    $align = $align ?? 'start';
@endphp

<nav
    @class([
        'eg-page-nav',
        'eg-page-nav--light' => $variant === 'light',
        'eg-page-nav--center' => $align === 'center',
    ])
    aria-label="{{ __('nav.page_navigation') }}"
>
    @foreach ($links as $link)
        <a
            href="{{ $link['href'] }}"
            @class([
                'eg-page-nav-btn',
                'eg-page-nav-btn--primary' => $link['primary'] ?? false,
            ])
            @if ($link['wireNavigate'] ?? false) wire:navigate @endif
        >
            @if (! empty($link['icon']))
                <i @class(['fa-solid', $link['icon']]) @if ($link['directional'] ?? false) data-icon-directional @endif></i>
            @endif
            <span>{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>
