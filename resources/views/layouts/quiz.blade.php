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
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('quiz.page_title') }} — {{ config('app.name') }}</title>

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
    @livewireStyles
</head>
<body class="eg-body eg-quiz-body">

    <main class="eg-quiz-main">
        {{ $slot }}
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
