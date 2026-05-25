<section class="section">
    <div class="section-head">
        <h2 class="section-title">{{ $section['title'] ?? '' }}</h2>
        @if (! empty($section['intro']))
            <p class="section-intro">{{ $section['intro'] }}</p>
        @endif
    </div>
    <div class="pill-wrap">
        @foreach ($section['items'] ?? [] as $item)
            <span class="pill"><span class="pill-star">&#9733;</span>{{ $item }}</span>
        @endforeach
    </div>
</section>
