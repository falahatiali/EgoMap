@php
    use App\Support\LocaleConfig;
    use App\Support\LocaleUrl;

    $current = app()->getLocale();
    $variant = $variant ?? 'default';
    $locales = LocaleConfig::supported();
    $labels = config('locales.labels', []);
    $shortLabels = config('locales.short_labels', []);
@endphp
<nav
    @class([
        'eg-lang-switch',
        'eg-lang-switch--nav' => $variant === 'nav',
    ])
    aria-label="{{ __('common.language') }}"
>
    @foreach ($locales as $locale)
        <a
            href="{{ LocaleUrl::switch($locale) }}"
            @class(['active' => $current === $locale])
            aria-current="{{ $current === $locale ? 'true' : 'false' }}"
            data-locale-switch="{{ $locale }}"
            @if (LocaleConfig::isRtl($locale)) lang="{{ $locale }}" @endif
            title="{{ $labels[$locale] ?? strtoupper($locale) }}"
        >
            <span class="eg-lang-switch__full">{{ $labels[$locale] ?? strtoupper($locale) }}</span>
            <span class="eg-lang-switch__short">{{ $shortLabels[$locale] ?? strtoupper($locale) }}</span>
        </a>
    @endforeach
</nav>
