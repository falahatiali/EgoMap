@php
    use App\Support\AdminNavigation;
    use App\Support\LocaleConfig;

    $user = auth()->user();
    $navItems = AdminNavigation::items($user);
    $activeNav = $activeNav ?? 'dashboard';
    $siteLocale = session('locale');
    $siteLocale = is_string($siteLocale) && LocaleConfig::isSupported($siteLocale)
        ? $siteLocale
        : LocaleConfig::default();
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr" class="eg-admin-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a0e17">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('admin.panel_title')) — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:500,600" rel="stylesheet">

    @vite(['resources/css/admin.css'])
    @livewireStyles
    @stack('head')
</head>
<body class="eg-admin-body">
    <div class="eg-admin-backdrop" aria-hidden="true"></div>

    <div class="eg-admin-shell">
        @include('partials.admin.sidebar', ['navItems' => $navItems, 'activeNav' => $activeNav])

        <div class="eg-admin-main">
            @include('partials.admin.topbar')

            <main class="eg-admin-content">
                @include('partials.admin.notice')
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
