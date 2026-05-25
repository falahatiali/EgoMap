<section class="section">
    <div class="section-head">
        <h2 class="section-title">{{ $section['title'] ?? '' }}</h2>
        @if (! empty($section['intro']))
            <p class="section-intro">{{ $section['intro'] }}</p>
        @endif
    </div>
    <table class="chip-table" cellpadding="0" cellspacing="0">
        @foreach ($section['items'] ?? [] as $index => $item)
            <tr>
                <td class="chip {{ $section['tone'] ?? 'success' }}">
                    <span class="chip-index">{{ $index + 1 }}</span>{{ $item }}
                </td>
            </tr>
        @endforeach
    </table>
</section>
