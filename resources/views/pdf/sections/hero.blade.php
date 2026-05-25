<table class="hero-wrap" cellpadding="0" cellspacing="0">
    <tr>
        <td class="hero-banner">
            <p class="hero-ribbon">{{ $section['ribbon'] ?? __('pdf.report_badge', locale: $locale) }}</p>
        </td>
    </tr>
    <tr>
        <td class="hero-body">
            @if (! empty($section['group']))
                <div class="hero-group">{{ $section['group'] }}</div>
            @endif
            <p class="hero-eyebrow">{{ $section['eyebrow'] ?? '' }}</p>
            @if (! empty($section['badge']))
                <div class="hero-badge">{{ $section['badge'] }}</div>
            @endif
            <h1 class="hero-title">{{ $section['title'] ?? '' }}</h1>
            @if (! empty($section['subtitle']))
                <p class="hero-subtitle">{{ $section['subtitle'] }}</p>
            @endif
            @if (! empty($section['meta']))
                <p class="hero-meta">{{ $section['meta'] }}</p>
            @endif
        </td>
    </tr>
</table>
