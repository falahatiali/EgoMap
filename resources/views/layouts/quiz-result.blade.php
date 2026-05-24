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
    <meta name="theme-color" content="#f8f7ff">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('quiz.your_result') }} — {{ config('app.name') }}</title>

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
</head>
<body class="eg-body eg-result-body">
    <main class="eg-result-main">
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
