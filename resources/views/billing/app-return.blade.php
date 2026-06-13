<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $success ? __('pricing.checkout_success_title') : __('pricing.page_title') }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b1220;
            --card: #111827;
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #22c55e;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            background: radial-gradient(circle at top, #132038, var(--bg));
            color: var(--text);
        }

        .card {
            width: min(100%, 420px);
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 20px;
            padding: 32px 28px;
            text-align: center;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 1.5rem;
            letter-spacing: -0.02em;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .hint {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(148, 163, 184, 0.12);
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="card">
        @if ($success)
            <h1>{{ __('pricing.checkout_success_title') }}</h1>
            <p>{{ __('pricing.checkout_success_body') }}</p>
        @elseif ($cancelled)
            <h1>{{ __('pricing.page_title') }}</h1>
            <p>{{ __('pricing.checkout_cancelled') }}</p>
        @else
            <h1>{{ __('pricing.page_title') }}</h1>
            <p>{{ __('pricing.mobile_return_hint') }}</p>
        @endif

        <p class="hint">{{ __('pricing.mobile_return_hint') }}</p>
    </div>
</body>
</html>
