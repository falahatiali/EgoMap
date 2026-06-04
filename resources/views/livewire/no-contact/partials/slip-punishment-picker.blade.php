<div class="eg-gm-slip-punishments eg-glass mb-4" role="dialog" aria-labelledby="eg-slip-punish-title">
    <h3 id="eg-slip-punish-title" class="h5 mb-2">{{ __('no_contact.slip_punishment_title') }}</h3>
    <p class="small eg-text-muted mb-3">{{ __('no_contact.slip_punishment_body') }}</p>

    <div class="eg-gm-punishment-options mb-3">
        @foreach ($punishmentChoices as $choice)
            <label
                @class(['eg-gm-punishment-option', 'eg-gm-punishment-option--selected' => $selectedPunishmentId === ($choice['id'] ?? null)])
                wire:key="punishment-choice-{{ $choice['id'] ?? $loop->index }}"
            >
                <input
                    type="radio"
                    wire:model.live="selectedPunishmentId"
                    value="{{ $choice['id'] }}"
                    name="selectedPunishmentId"
                    class="eg-gm-punishment-option__input"
                >
                <span class="eg-gm-punishment-option__type">
                    @if (($choice['type'] ?? '') === 'physical')
                        <i class="fa-solid fa-dumbbell"></i>
                    @elseif (($choice['type'] ?? '') === 'writing')
                        <i class="fa-solid fa-pen"></i>
                    @else
                        <i class="fa-solid fa-brain"></i>
                    @endif
                </span>
                <span class="eg-gm-punishment-option__body">
                    <strong>{{ $choice['title'] ?? '' }}</strong>
                    <span class="d-block small eg-text-muted">{{ $choice['description'] ?? '' }}</span>
                    <span class="d-block small eg-gm-punishment-option__meta">
                        {{ __('no_contact.punishment_est_minutes', ['min' => eg_num($choice['estimated_minutes'] ?? 5)]) }}
                        · {{ __('no_contact.punishment_difficulty_'.$choice['difficulty']) }}
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    @error('selectedPunishmentId')
        <p class="text-danger small mb-2">{{ $message }}</p>
    @enderror

    <div class="eg-gm-slip-actions">
        <button type="button" class="eg-gm-shield-btn eg-gm-shield-btn--primary" wire:click="chooseSlipPunishment" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="chooseSlipPunishment">{{ __('no_contact.slip_punishment_confirm') }}</span>
            <span wire:loading wire:target="chooseSlipPunishment">…</span>
        </button>
    </div>
</div>
