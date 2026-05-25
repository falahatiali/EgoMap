@php
    use App\Support\LocaleConfig;

    $locale = app()->getLocale();
    $isRtl = LocaleConfig::isRtl($locale);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#070b14">
    <meta name="description" content="{{ __('common.tagline') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="locale-url" content="{{ url('/locale/__LOCALE__') }}">

    <title>@yield('title', __('common.brand')) — {{ __('common.tagline') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|vazirmatn:400,500,600,700" rel="stylesheet">

    <link
        id="eg-bootstrap"
        rel="stylesheet"
        href="{{ Vite::asset($isRtl ? 'resources/css/bootstrap-rtl.css' : 'resources/css/bootstrap-ltr.css') }}"
        data-ltr="{{ Vite::asset('resources/css/bootstrap-ltr.css') }}"
        data-rtl="{{ Vite::asset('resources/css/bootstrap-rtl.css') }}"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')

    <script type="application/json" id="eg-i18n">@json($i18nBundle)</script>
</head>
<body class="eg-body">
    <div class="eg-bg" aria-hidden="true"></div>

    @include('partials.navbar')

    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('partials.footer')

    @hasSection('sticky_cta')
        <div class="eg-sticky-cta d-lg-none">
            @yield('sticky_cta')
        </div>
    @endif

    @stack('scripts')
</body>
</html>
