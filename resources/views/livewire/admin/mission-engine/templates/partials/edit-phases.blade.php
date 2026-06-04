<div class="eg-admin-panel eg-admin-panel--padded mb-4">
    @include('livewire.admin.mission-engine.templates.partials.section-lead', [
        'title' => __('admin.mission_engine.tab_phases'),
        'description' => __('admin.mission_engine.phases_intro'),
        'icon' => 'fa-layer-group',
    ])

    <div class="eg-admin-builder-toolbar">
        <button type="button" class="eg-admin-btn eg-admin-btn--primary" wire:click="addPhase">
            <i class="fa-solid fa-plus me-1"></i>
            {{ __('admin.mission_engine.add_phase') }}
        </button>
    </div>
</div>

@if ($phaseDrafts === [])
    <div class="eg-admin-panel eg-admin-panel--padded eg-admin-empty-state">
        <i class="fa-solid fa-layer-group fa-2x mb-3" aria-hidden="true"></i>
        <p class="mb-0">{{ __('admin.mission_engine.phases_empty') }}</p>
    </div>
@else
    <div class="eg-admin-phase-grid">
        @foreach ($phaseDrafts as $index => $phase)
            <article class="eg-admin-phase-card eg-admin-panel eg-admin-panel--padded" wire:key="phase-{{ $phase['id'] }}">
                <header class="eg-admin-field-card__head">
                    <span class="eg-admin-tag eg-admin-input--mono">{{ $phase['slug'] }}</span>
                    <div class="eg-admin-field-card__actions">
                        <button type="button" class="eg-admin-btn eg-admin-btn--sm" wire:click="movePhase({{ $phase['id'] }}, 'up')">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <button type="button" class="eg-admin-btn eg-admin-btn--sm" wire:click="movePhase({{ $phase['id'] }}, 'down')">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                        <button type="button" class="eg-admin-btn eg-admin-btn--sm eg-admin-btn--danger" wire:click="deletePhase({{ $phase['id'] }})" wire:confirm="{{ __('admin.mission_engine.delete_phase_confirm') }}">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </header>

                <div class="eg-admin-form-grid eg-admin-form-grid--compact mt-3">
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.phase_slug') }}</span>
                        <input type="text" class="eg-admin-input eg-admin-input--mono" wire:model="phaseDrafts.{{ $index }}.slug">
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.duration_days') }}</span>
                        <input type="number" min="1" class="eg-admin-input" wire:model="phaseDrafts.{{ $index }}.duration_days">
                    </label>
                </div>

                <div class="eg-admin-field-row mt-3">
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.title_en') }}</span>
                        <input type="text" class="eg-admin-input" wire:model="phaseDrafts.{{ $index }}.title_en">
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.title_fa') }}</span>
                        <input type="text" class="eg-admin-input" wire:model="phaseDrafts.{{ $index }}.title_fa" dir="rtl">
                    </label>
                </div>

                <label class="eg-admin-field mt-3">
                    <span>{{ __('admin.mission_engine.description_en') }}</span>
                    <textarea class="eg-admin-textarea" rows="3" wire:model="phaseDrafts.{{ $index }}.description_en"></textarea>
                </label>

                <div class="text-end mt-4 pt-3 eg-admin-field-card__footer">
                    <button type="button" class="eg-admin-btn eg-admin-btn--primary eg-admin-btn--sm" wire:click="savePhase({{ $phase['id'] }})">
                        {{ __('admin.mission_engine.save_phase') }}
                    </button>
                </div>
            </article>
        @endforeach
    </div>
@endif
