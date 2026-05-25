<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $document->locale) }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->meta->title }}</title>
    <style>
        @page { margin: 0; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: {{ $document->theme->text }};
            background: {{ $document->theme->background }};
            font-size: 10.5pt;
            line-height: 1.55;
        }

        .page { padding: 0 0 28px; }

        .top-accent {
            height: 8px;
            background: {{ $document->theme->accent }};
            width: 100%;
        }

        .page-inner { padding: 22px 30px 0; }

        .brand-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .brand-bar td { vertical-align: middle; }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: {{ $document->theme->accent }};
            color: #ffffff;
            text-align: center;
            font-weight: 800;
            font-size: 20px;
            line-height: 44px;
        }

        .brand-name {
            font-size: 12pt;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: {{ $document->theme->text }};
        }

        .brand-tagline {
            font-size: 8.5pt;
            color: {{ $document->theme->textMuted }};
            margin-top: 2px;
        }

        .generated-label {
            font-size: 8.5pt;
            color: {{ $document->theme->textMuted }};
            text-align: {{ $direction === 'rtl' ? 'left' : 'right' }};
            background: {{ $document->theme->surface }};
            border: 1px solid {{ $document->theme->border }};
            border-radius: 999px;
            padding: 6px 12px;
        }

        /* Hero */
        .hero-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid {{ $document->theme->border }};
        }

        .hero-banner {
            background: {{ $document->theme->accent }};
            color: #ffffff;
            padding: 14px 22px;
            text-align: center;
        }

        .hero-ribbon {
            font-size: 8pt;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            opacity: 0.92;
            margin: 0;
        }

        .hero-body {
            background: {{ $document->theme->surface }};
            padding: 24px 22px 20px;
            text-align: center;
        }

        .hero-group {
            display: inline-block;
            background: {{ $document->theme->groupBackground }};
            color: {{ $document->theme->accentDark }};
            border: 1px solid {{ $document->theme->border }};
            border-radius: 999px;
            padding: 4px 14px;
            font-size: 8.5pt;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .hero-badge {
            display: inline-block;
            min-width: 88px;
            padding: 10px 22px;
            border-radius: 16px;
            background: {{ $document->theme->accentSoft }};
            border: 2px solid {{ $document->theme->accent }};
            color: {{ $document->theme->accentDark }};
            font-size: 22pt;
            font-weight: 800;
            letter-spacing: 0.14em;
            margin-bottom: 10px;
        }

        .hero-eyebrow {
            font-size: 8pt;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: {{ $document->theme->textMuted }};
            margin: 0 0 8px;
        }

        .hero-title {
            margin: 0 0 8px;
            font-size: 22pt;
            line-height: 1.15;
            color: {{ $document->theme->text }};
        }

        .hero-subtitle {
            margin: 0 auto;
            color: {{ $document->theme->textMuted }};
            font-size: 10.5pt;
            max-width: 92%;
        }

        .hero-meta {
            margin-top: 12px;
            font-size: 8.5pt;
            color: {{ $document->theme->textMuted }};
        }

        /* Stats */
        .stat-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 18px;
        }

        .stat-card {
            background: {{ $document->theme->surface }};
            border: 1px solid {{ $document->theme->border }};
            border-radius: 14px;
            padding: 12px 10px;
            text-align: center;
            vertical-align: top;
            width: 25%;
        }

        .stat-card.primary {
            background: {{ $document->theme->accentSoft }};
            border-color: {{ $document->theme->accent }};
        }

        .stat-card.group {
            background: {{ $document->theme->groupBackground }};
            border-color: {{ $document->theme->accent }};
        }

        .stat-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: {{ $document->theme->textMuted }};
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 12pt;
            font-weight: 800;
            color: {{ $document->theme->text }};
            line-height: 1.2;
        }

        .stat-card.primary .stat-value { color: {{ $document->theme->accentDark }}; }

        /* Sections */
        .section {
            background: {{ $document->theme->surface }};
            border: 1px solid {{ $document->theme->border }};
            border-radius: 18px;
            padding: 18px 20px 16px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .section-head {
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid {{ $document->theme->accentSoft }};
        }

        .section-title {
            margin: 0;
            font-size: 13pt;
            font-weight: 800;
            color: {{ $document->theme->accentDark }};
        }

        .section-intro {
            margin: 6px 0 0;
            font-size: 9.5pt;
            color: {{ $document->theme->textMuted }};
            line-height: 1.5;
        }

        .overview-body {
            margin: 0;
            font-size: 10.5pt;
            color: {{ $document->theme->text }};
            line-height: 1.65;
            background: {{ $document->theme->groupBackground }};
            border-radius: 12px;
            padding: 14px 16px;
            border-{{ $direction === 'rtl' ? 'right' : 'left' }}: 4px solid {{ $document->theme->accent }};
        }

        /* Type letters */
        .letters-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .letter-card {
            background: #f8fafc;
            border: 1px solid {{ $document->theme->border }};
            border-radius: 14px;
            padding: 12px 8px;
            text-align: center;
            width: 25%;
            vertical-align: top;
        }

        .letter-card.is-active {
            background: {{ $document->theme->accentSoft }};
            border-color: {{ $document->theme->accent }};
        }

        .letter-pair {
            font-size: 8pt;
            color: {{ $document->theme->textMuted }};
            margin-bottom: 6px;
        }

        .letter-choice {
            font-size: 18pt;
            font-weight: 800;
            color: {{ $document->theme->accentDark }};
            line-height: 1;
        }

        .letter-card.is-active .letter-choice {
            color: {{ $document->theme->accent }};
        }

        /* Dimensions */
        .axis-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }

        .axis-card { border-radius: 14px; padding: 12px 14px; border: 1px solid {{ $document->theme->border }}; }

        .axis-name {
            font-size: 8.5pt;
            font-weight: 700;
            color: {{ $document->theme->textMuted }};
            margin-bottom: 8px;
        }

        .axis-labels { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .axis-labels td { font-size: 10pt; font-weight: 800; color: {{ $document->theme->textMuted }}; }
        .axis-labels td.active { color: {{ $document->theme->text }}; }
        .axis-labels td.percent { text-align: center; font-size: 11pt; color: {{ $document->theme->text }}; }

        .axis-track { width: 100%; height: 12px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .axis-fill { height: 12px; border-radius: 999px; }
        .axis-pref { margin: 8px 0 0; font-size: 8.5pt; color: {{ $document->theme->textMuted }}; }
        .axis-desc { margin: 4px 0 0; font-size: 8pt; color: {{ $document->theme->textMuted }}; line-height: 1.45; }

        /* Strengths */
        .chip-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .chip {
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 10pt;
            color: {{ $document->theme->text }};
            vertical-align: top;
        }

        .chip.success { background: #ecfdf5; border: 1px solid #a7f3d0; }
        .chip-index {
            display: inline-block;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            border-radius: 999px;
            background: #10b981;
            color: #fff;
            font-size: 8pt;
            font-weight: 800;
            margin-{{ $direction === 'rtl' ? 'left' : 'right' }}: 8px;
        }

        /* Growth */
        .note-item {
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 10pt;
            margin-bottom: 8px;
        }

        .note-item.warm { background: #fff7ed; border: 1px solid #fed7aa; color: {{ $document->theme->text }}; }
        .note-bullet { color: #ea580c; font-weight: 800; margin-{{ $direction === 'rtl' ? 'left' : 'right' }}: 8px; }

        /* Highlight cards */
        .highlight {
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 14px;
            page-break-inside: avoid;
            border: 1px solid {{ $document->theme->border }};
        }

        .highlight.blue { background: #eff6ff; border-color: #bfdbfe; }
        .highlight.rose { background: #fff1f2; border-color: #fecdd3; }

        .highlight-icon {
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 10px;
            font-size: 14px;
            margin-{{ $direction === 'rtl' ? 'left' : 'right' }}: 8px;
            vertical-align: middle;
        }

        .highlight.blue .highlight-icon { background: #2563eb; color: #fff; }
        .highlight.rose .highlight-icon { background: #e11d48; color: #fff; }

        .highlight-title {
            display: inline;
            font-size: 12pt;
            font-weight: 800;
            color: {{ $document->theme->text }};
            vertical-align: middle;
        }

        .highlight-body {
            margin: 10px 0 0;
            font-size: 10pt;
            line-height: 1.65;
            color: {{ $document->theme->text }};
        }

        /* Pills */
        .pill-wrap { line-height: 2.2; }
        .pill {
            display: inline-block;
            background: {{ $document->theme->accentSoft }};
            border: 1px solid {{ $document->theme->accent }};
            color: {{ $document->theme->accentDark }};
            border-radius: 999px;
            padding: 5px 14px;
            font-size: 9.5pt;
            font-weight: 600;
            margin: 0 5px 6px 0;
        }

        .pill-star { color: #f59e0b; margin-{{ $direction === 'rtl' ? 'left' : 'right' }}: 4px; }

        /* Callouts */
        .callout {
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 14px;
            text-align: center;
            page-break-inside: avoid;
        }

        .callout.tips {
            background: {{ $document->theme->groupBackground }};
            border: 1px dashed {{ $document->theme->accent }};
        }

        .callout.action {
            background: {{ $document->theme->accentSoft }};
            border: 1px solid {{ $document->theme->accent }};
        }

        .callout-title { margin: 0 0 6px; font-size: 12pt; font-weight: 800; color: {{ $document->theme->accentDark }}; }
        .callout-body { margin: 0 0 10px; font-size: 9.5pt; color: {{ $document->theme->textMuted }}; line-height: 1.55; }

        .callout-link {
            display: inline-block;
            background: {{ $document->theme->accent }};
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 999px;
            padding: 9px 20px;
            font-size: 10pt;
            font-weight: 800;
        }

        .footer {
            margin: 10px 30px 0;
            padding-top: 14px;
            border-top: 1px solid {{ $document->theme->border }};
            text-align: center;
            font-size: 8pt;
            color: {{ $document->theme->textMuted }};
        }
    </style>
</head>
<body>
    <div class="top-accent"></div>
    <div class="page">
        <div class="page-inner">
            <table class="brand-bar">
                <tr>
                    <td style="width: 52px;">
                        <div class="brand-mark">E</div>
                    </td>
                    <td>
                        <div class="brand-name">{{ $document->meta->brand }}</div>
                        <div class="brand-tagline">{{ __('pdf.brand_tagline', locale: $document->locale) }}</div>
                    </td>
                    @if ($document->meta->generatedAtLabel)
                        <td style="width: 1%; white-space: nowrap;">
                            <span class="generated-label">{{ $document->meta->generatedAtLabel }}</span>
                        </td>
                    @endif
                </tr>
            </table>

            @foreach ($document->sections as $section)
                @include('pdf.sections.'.($section['type'] ?? 'paragraph'), [
                    'section' => $section,
                    'theme' => $document->theme,
                    'direction' => $direction,
                    'locale' => $document->locale,
                ])
            @endforeach
        </div>

        @if ($document->meta->footerNote)
            <div class="footer">{{ $document->meta->footerNote }}</div>
        @endif
    </div>
</body>
</html>
