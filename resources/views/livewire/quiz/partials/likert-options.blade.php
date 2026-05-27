<div class="eg-quiz-options eg-quiz-options-grid">
    @foreach ($likertLabels as $index => $label)
        @php $value = (string) ($index + 1); @endphp
        <button
            type="button"
            class="eg-quiz-option"
            data-accent="{{ ['violet', 'magenta', 'purple', 'teal', 'gold'][$index] ?? 'purple' }}"
            data-hotkey="{{ $value }}"
            wire:click="selectAnswer('{{ $value }}')"
            wire:loading.attr="disabled"
            wire:target="selectAnswer"
        >
            <span class="eg-quiz-option-icon">{{ $value }}</span>
            <span class="eg-quiz-option-label">{{ $label }}</span>
            <span class="eg-quiz-option-key">{{ $value }}</span>
        </button>
    @endforeach
</div>
