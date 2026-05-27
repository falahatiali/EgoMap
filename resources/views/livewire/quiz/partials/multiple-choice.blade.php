@php
    $maxSelections = (int) ($question->config['max_selections'] ?? 3);
    $selectedCount = count($multiSelection);
@endphp

<form
    wire:submit.prevent="submitMultiChoice"
    class="eg-quiz-multi-panel eg-quiz-multi-form"
>
    <p class="eg-quiz-selection-hint" aria-live="polite">
        @if ($selectedCount === 0)
            {{ __('quiz.select_up_to', ['max' => eg_num($maxSelections)]) }}
        @else
            {{ __('quiz.selection_count', [
                'current' => eg_num($selectedCount),
                'max' => eg_num($maxSelections),
            ]) }}
        @endif
    </p>

    <div
        @class([
            'eg-quiz-options',
            'eg-quiz-options-grid' => $options->count() >= 2,
        ])
        wire:loading.class="eg-quiz-options-busy"
        wire:target="toggleMultiChoice,submitMultiChoice"
    >
        @foreach ($options as $index => $option)
            @php
                $accent = $option->meta['accent'] ?? 'purple';
                $icon = $option->meta['icon'] ?? 'fa-circle';
                $hotkey = (string) ($index + 1);
                $optionValue = (string) $option->value;
                $isChecked = in_array($optionValue, $multiSelection, true);
                $isLocked = $maxSelections > 0 && $selectedCount >= $maxSelections && ! $isChecked;
            @endphp
            <button
                type="button"
                @class([
                    'eg-quiz-option',
                    'is-active' => $isChecked,
                    'is-locked' => $isLocked,
                ])
                data-accent="{{ $accent }}"
                data-hotkey="{{ $hotkey }}"
                wire:click.prevent="toggleMultiChoice('{{ $optionValue }}')"
                wire:loading.attr="disabled"
                wire:target="toggleMultiChoice"
                @disabled($isLocked)
                aria-pressed="{{ $isChecked ? 'true' : 'false' }}"
            >
                <span class="eg-quiz-option-icon" aria-hidden="true">
                    <i class="fa-solid {{ $icon }} eg-quiz-option-symbol"></i>
                    <i class="fa-solid fa-check eg-quiz-option-check"></i>
                </span>
                <span class="eg-quiz-option-label">{{ $option->getTranslation('label', $quizLocale) }}</span>
                <span class="eg-quiz-option-key">{{ eg_num($hotkey) }}</span>
                @if ($isChecked)
                    <span class="eg-quiz-option-selected-ring" aria-hidden="true"></span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="eg-quiz-multi-actions">
        <button
            type="submit"
            class="eg-quiz-next-btn {{ $selectedCount === 0 ? 'is-muted' : '' }}"
            @disabled($selectedCount === 0)
            wire:loading.attr="disabled"
            wire:target="submitMultiChoice"
        >
            <i class="fa-solid fa-arrow-right" data-icon-directional></i>
            <span>{{ __('quiz.continue') }}</span>
        </button>
        <button
            type="button"
            class="eg-quiz-skip-btn"
            wire:click.prevent="skipQuestion"
            wire:loading.attr="disabled"
            wire:target="skipQuestion"
        >
            <span>{{ __('quiz.skip') }}</span>
        </button>
    </div>
</form>
