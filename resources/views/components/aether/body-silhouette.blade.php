@props([
    'variant' => 'average',
    'gender' => 'male',
    'selected' => false,
    'glow' => 'emerald',
])

@php
    $widthScale = match ($variant) {
        'slender', 'lean' => 0.82,
        'average', 'athletic' => 1.0,
        'stocky', 'defined' => 1.12,
        'heavy', 'muscular' => 1.28,
        default => 1.0,
    };

    $definition = match ($variant) {
        'defined', 'muscular' => 1,
        'athletic', 'stocky' => 0.55,
        default => 0.25,
    };

    $hipScale = $gender === 'female' ? 1.08 : 1.0;
@endphp

<div @class([
    'eg-aether-body-card__figure',
    'is-selected' => $selected,
    'eg-aether-body-card__figure--'.$glow,
]) aria-hidden="true">
    <svg viewBox="0 0 120 220" class="eg-aether-body-card__svg" role="presentation">
        <defs>
            <linearGradient id="body-fill-{{ $variant }}-{{ $gender }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="rgba(255,255,255,0.92)" />
                <stop offset="100%" stop-color="rgba(148,163,184,0.55)" />
            </linearGradient>
            <filter id="body-glow-{{ $variant }}-{{ $gender }}" x="-30%" y="-20%" width="160%" height="160%">
                <feGaussianBlur stdDeviation="6" result="blur" />
                <feMerge>
                    <feMergeNode in="blur" />
                    <feMergeNode in="SourceGraphic" />
                </feMerge>
            </filter>
        </defs>

        <ellipse cx="60" cy="205" rx="34" ry="6" fill="rgba(52,211,153,0.12)" />

        <g transform="translate(60 110) scale({{ $widthScale * $hipScale }} 1) translate(-60 -110)" filter="{{ $selected ? 'url(#body-glow-'.$variant.'-'.$gender.')' : '' }}">
            {{-- Head --}}
            <ellipse cx="60" cy="28" rx="14" ry="16" fill="url(#body-fill-{{ $variant }}-{{ $gender }})" />

            {{-- Neck --}}
            <rect x="54" y="42" width="12" height="10" rx="4" fill="url(#body-fill-{{ $variant }}-{{ $gender }})" />

            {{-- Torso --}}
            <path
                d="M42 52 C38 70, 36 92, 38 118 C40 132, 46 138, 60 140 C74 138, 80 132, 82 118 C84 92, 82 70, 78 52 Z"
                fill="url(#body-fill-{{ $variant }}-{{ $gender }})"
            />

            @if ($definition >= 0.5)
                <path
                    d="M48 68 C52 78, 56 86, 60 88 C64 86, 68 78, 72 68"
                    fill="none"
                    stroke="rgba(15,23,42,0.35)"
                    stroke-width="1.2"
                />
                <path
                    d="M46 96 C50 104, 55 108, 60 109 C65 108, 70 104, 74 96"
                    fill="none"
                    stroke="rgba(15,23,42,0.28)"
                    stroke-width="1"
                />
            @endif

            @if ($definition >= 1)
                <path d="M44 62 L48 82 L52 62" fill="rgba(15,23,42,0.18)" />
                <path d="M76 62 L72 82 L68 62" fill="rgba(15,23,42,0.18)" />
            @endif

            {{-- Arms --}}
            <path
                d="M42 56 C28 68, 22 92, 24 118 C25 128, 30 132, 34 128 C36 110, 38 86, 42 68 Z"
                fill="url(#body-fill-{{ $variant }}-{{ $gender }})"
            />
            <path
                d="M78 56 C92 68, 98 92, 96 118 C95 128, 90 132, 86 128 C84 110, 82 86, 78 68 Z"
                fill="url(#body-fill-{{ $variant }}-{{ $gender }})"
            />

            {{-- Legs --}}
            <path
                d="M46 138 C44 158, 42 182, 44 202 C46 208, 52 208, 54 202 C56 182, 57 158, 58 142 Z"
                fill="url(#body-fill-{{ $variant }}-{{ $gender }})"
            />
            <path
                d="M74 138 C76 158, 78 182, 76 202 C74 208, 68 208, 66 202 C64 182, 63 158, 62 142 Z"
                fill="url(#body-fill-{{ $variant }}-{{ $gender }})"
            />
        </g>
    </svg>
</div>
