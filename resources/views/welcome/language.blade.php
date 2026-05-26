@php
    use App\Support\LocaleUrl;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#05070A">
    <title>{{ __('welcome.title') }} — {{ __('common.brand') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700|vazirmatn:400,600,700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            font-family: Inter, system-ui, sans-serif;
            background: #05070a;
            color: #d1d5db;
        }
        .wl {
            max-width: 28rem;
            width: 100%;
            text-align: center;
        }
        .wl__brand {
            font-size: 1.35rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.35rem;
        }
        .wl__title {
            font-size: clamp(1.5rem, 4vw, 1.85rem);
            font-weight: 700;
            color: #fff;
            margin: 0 0 0.75rem;
        }
        .wl__sub {
            font-size: 0.95rem;
            line-height: 1.65;
            color: #9ca3af;
            margin: 0 0 2rem;
        }
        .wl__actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .wl__btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .wl__btn--en {
            background: #10b981;
            color: #05070a;
            border-color: #10b981;
        }
        .wl__btn--en:hover { background: #34d399; color: #05070a; }
        .wl__btn--fa {
            background: transparent;
            color: #fbbf24;
            border-color: rgba(251, 191, 36, 0.5);
            font-family: Vazirmatn, system-ui, sans-serif;
        }
        .wl__btn--fa:hover { background: rgba(251, 191, 36, 0.1); }
        .wl__note {
            margin-top: 1.5rem;
            font-size: 0.78rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="wl">
        <p class="wl__brand">{{ __('common.brand') }}</p>
        <h1 class="wl__title">{{ __('welcome.title') }}</h1>
        <p class="wl__sub">{{ __('welcome.subtitle') }}</p>
        <div class="wl__actions">
            <a href="{{ LocaleUrl::home('en') }}" class="wl__btn wl__btn--en">{{ __('welcome.cta_en') }}</a>
            <a href="{{ LocaleUrl::home('fa') }}" class="wl__btn wl__btn--fa" lang="fa" dir="rtl">{{ __('welcome.cta_fa') }}</a>
        </div>
        <p class="wl__note">{{ __('welcome.note') }}</p>
    </div>
</body>
</html>
