<section class="section">
    <div class="section-head">
        <h2 class="section-title">{{ $section['title'] ?? '' }}</h2>
        @if (! empty($section['intro']))
            <p class="section-intro">{{ $section['intro'] }}</p>
        @endif
    </div>
    <table class="letters-table" cellpadding="0" cellspacing="0">
        <tr>
            @foreach ($section['items'] ?? [] as $item)
                <td class="letter-card is-active">
                    <div class="letter-pair">{{ $item['left'] ?? '' }} / {{ $item['right'] ?? '' }}</div>
                    <div class="letter-choice">{{ $item['preference'] ?? '' }}</div>
                </td>
            @endforeach
        </tr>
    </table>
</section>
