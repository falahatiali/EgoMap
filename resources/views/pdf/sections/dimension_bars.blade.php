<section class="section">
    <div class="section-head">
        <h2 class="section-title">{{ $section['title'] ?? '' }}</h2>
        @if (! empty($section['intro']))
            <p class="section-intro">{{ $section['intro'] }}</p>
        @endif
    </div>
    <table class="axis-table" cellpadding="0" cellspacing="0">
        @foreach ($section['items'] ?? [] as $item)
            <tr>
                <td>
                    <div class="axis-card" style="background: {{ $item['soft'] ?? $theme->accentSoft }}; border-color: {{ $item['color'] ?? $theme->accent }};">
                        @if (! empty($item['label']))
                            <div class="axis-name">{{ $item['label'] }}</div>
                        @endif
                        <table class="axis-labels" cellpadding="0" cellspacing="0">
                            <tr>
                                <td @class(['active' => ! ($item['prefers_right'] ?? false)])>{{ $item['left'] ?? '' }}</td>
                                <td class="percent">{{ $item['percent'] ?? 0 }}%</td>
                                <td @class(['active' => ($item['prefers_right'] ?? false)]) style="text-align: {{ $direction === 'rtl' ? 'left' : 'right' }};">{{ $item['right'] ?? '' }}</td>
                            </tr>
                        </table>
                        <div class="axis-track">
                            <div class="axis-fill" style="width: {{ $item['percent'] ?? 0 }}%; background: {{ $item['color'] ?? $theme->accent }};"></div>
                        </div>
                        <p class="axis-pref">{{ __('quiz.dimension_preference', ['letter' => $item['preference'] ?? ''], locale: $locale) }}</p>
                        @if (! empty($item['description']))
                            <p class="axis-desc">{{ $item['description'] }}</p>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </table>
</section>
