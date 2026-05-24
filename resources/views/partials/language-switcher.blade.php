@php
    $current = app()->getLocale();
@endphp
<nav class="eg-lang-switch" aria-label="{{ __('common.language') }}">
    <button
        type="button"
        data-locale-switch="en"
        @class(['active' => $current === 'en'])
        aria-pressed="{{ $current === 'en' ? 'true' : 'false' }}"
    >{{ __('common.english') }}</button>
    <button
        type="button"
        data-locale-switch="fa"
        @class(['active' => $current === 'fa'])
        aria-pressed="{{ $current === 'fa' ? 'true' : 'false' }}"
    >{{ __('common.persian') }}</button>
</nav>
