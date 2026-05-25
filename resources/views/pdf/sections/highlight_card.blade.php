@php
    $tone = $section['tone'] ?? 'blue';
    $icon = match ($section['icon'] ?? '') {
        'heart' => '&#9829;',
        'briefcase' => '&#9733;',
        default => '&#9670;',
    };
@endphp

<div class="highlight {{ $tone }}">
    <span class="highlight-icon">{!! $icon !!}</span>
    <span class="highlight-title">{{ $section['title'] ?? '' }}</span>
    <p class="highlight-body">{{ $section['body'] ?? '' }}</p>
</div>
