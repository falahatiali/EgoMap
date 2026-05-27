<div
    @class([
        'eg-quiz-options',
        'eg-quiz-options-grid' => $options->count() >= 2,
    ])
    wire:loading.class="eg-quiz-options-busy"
    wire:target="selectAnswer"
>
    @foreach ($options as $index => $option)
        @php
            $accent = $option->meta['accent'] ?? 'purple';
            $icon = $option->meta['icon'] ?? 'fa-circle';
            $hotkey = (string) ($index + 1);
            $optionValue = (string) $option->value;
            $isActive = ($singleSelection ?? '') === $optionValue;
        @endphp
        <button
            type="button"
            @class(['eg-quiz-option', 'is-active' => $isActive])
            data-accent="{{ $accent }}"
            data-hotkey="{{ $hotkey }}"
            wire:click.prevent="selectAnswer('{{ $optionValue }}')"
            wire:loading.attr="disabled"
            wire:target="selectAnswer"
            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
        >
            <span class="eg-quiz-option-icon" aria-hidden="true">
                <i class="fa-solid {{ $icon }} eg-quiz-option-symbol"></i>
                <i class="fa-solid fa-check eg-quiz-option-check"></i>
            </span>
            <span class="eg-quiz-option-label">{{ $option->getTranslation('label', $quizLocale) }}</span>
            <span class="eg-quiz-option-key">{{ $hotkey }}</span>
            @if ($isActive)
                <span class="eg-quiz-option-selected-ring" aria-hidden="true"></span>
            @endif
        </button>
    @endforeach
</div>
