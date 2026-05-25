<section class="section">
    <div class="section-head">
        <h2 class="section-title">{{ $section['title'] ?? '' }}</h2>
        @if (! empty($section['intro']))
            <p class="section-intro">{{ $section['intro'] }}</p>
        @endif
    </div>
    @foreach ($section['items'] ?? [] as $item)
        <div class="note-item {{ $section['tone'] ?? 'warm' }}">
            <span class="note-bullet">&#9679;</span>{{ $item }}
        </div>
    @endforeach
</section>
