<section @class(['callout', $section['style'] ?? 'action'])>
    <h3 class="callout-title">{{ $section['title'] ?? '' }}</h3>
    @if (! empty($section['body']))
        <p class="callout-body">{{ $section['body'] }}</p>
    @endif
    @if (! empty($section['url']))
        <a class="callout-link" href="{{ $section['url'] }}">{{ $section['label'] ?? $section['url'] }}</a>
    @endif
</section>
