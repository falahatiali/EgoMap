@php
    $task = $pending['punishment'] ?? [];
@endphp

<div class="eg-gm-pending-punishment eg-glass mb-4" role="status">
    <p class="eg-gm-pending-punishment__kicker small mb-1">{{ __('no_contact.pending_punishment_kicker') }}</p>
    <h3 class="h6 mb-2">{{ $task['title'] ?? '' }}</h3>
    <p class="small eg-text-muted mb-3">{{ $task['description'] ?? '' }}</p>
    <p class="small mb-3">{{ __('no_contact.pending_punishment_recovery') }}</p>
    <button type="button" class="eg-gm-shield-btn eg-gm-shield-btn--primary" wire:click="completePendingPunishment" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="completePendingPunishment">
            <i class="fa-solid fa-check me-2"></i>
            {{ __('no_contact.pending_punishment_done') }}
        </span>
        <span wire:loading wire:target="completePendingPunishment">…</span>
    </button>
</div>
