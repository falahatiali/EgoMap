<div class="eg-admin-panel eg-admin-panel--padded mb-4">
    @include('livewire.admin.mission-engine.templates.partials.section-lead', [
        'title' => __('admin.mission_engine.tab_fields'),
        'description' => __('admin.mission_engine.fields_intro'),
        'icon' => 'fa-list-check',
    ])

    <div class="eg-admin-builder-toolbar">
        <button type="button" class="eg-admin-btn eg-admin-btn--primary" wire:click="addField">
            <i class="fa-solid fa-plus me-1"></i>
            {{ __('admin.mission_engine.add_field') }}
        </button>
    </div>
</div>

@if ($fieldDrafts === [])
    <div class="eg-admin-panel eg-admin-panel--padded eg-admin-empty-state">
        <i class="fa-solid fa-list-check fa-2x mb-3" aria-hidden="true"></i>
        <p class="mb-0">{{ __('admin.mission_engine.fields_empty') }}</p>
    </div>
@else
    <div class="eg-admin-field-stack">
        @foreach ($fieldDrafts as $index => $field)
            <article class="eg-admin-field-card eg-admin-panel eg-admin-panel--padded" wire:key="field-{{ $field['id'] }}">
                <header class="eg-admin-field-card__head">
                    <span class="eg-admin-tag eg-admin-input--mono">{{ $field['field_key'] }}</span>
                    <div class="eg-admin-field-card__actions">
                        <button type="button" class="eg-admin-btn eg-admin-btn--sm" wire:click="moveField({{ $field['id'] }}, 'up')" title="{{ __('admin.mission_engine.move_up') }}">
                            <i class="fa-solid fa-arrow-up"></i>
                        </button>
                        <button type="button" class="eg-admin-btn eg-admin-btn--sm" wire:click="moveField({{ $field['id'] }}, 'down')" title="{{ __('admin.mission_engine.move_down') }}">
                            <i class="fa-solid fa-arrow-down"></i>
                        </button>
                        <button type="button" class="eg-admin-btn eg-admin-btn--sm eg-admin-btn--danger" wire:click="deleteField({{ $field['id'] }})" wire:confirm="{{ __('admin.mission_engine.delete_field_confirm') }}">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </header>

                <div class="eg-admin-form-grid eg-admin-form-grid--compact mt-3">
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.field_key') }}</span>
                        <input type="text" class="eg-admin-input eg-admin-input--mono" wire:model="fieldDrafts.{{ $index }}.field_key">
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.field_type') }}</span>
                        <select class="eg-admin-select" wire:model="fieldDrafts.{{ $index }}.field_type">
                            @foreach ($fieldTypeOptions as $type)
                                <option value="{{ $type->value }}">{{ str($type->value)->headline() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.field_section') }}</span>
                        <select class="eg-admin-select" wire:model="fieldDrafts.{{ $index }}.section">
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section }}">{{ str($section)->headline() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.field_capability') }}</span>
                        <select class="eg-admin-select" wire:model="fieldDrafts.{{ $index }}.capability_type_id">
                            <option value="">{{ __('admin.mission_engine.no_capability') }}</option>
                            @foreach ($capabilityTypes as $capability)
                                <option value="{{ $capability->id }}">{{ $capability->getTranslation('name', 'en', true) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="eg-admin-field-row mt-3">
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.label_en') }}</span>
                        <input type="text" class="eg-admin-input" wire:model="fieldDrafts.{{ $index }}.label_en">
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.label_fa') }}</span>
                        <input type="text" class="eg-admin-input" wire:model="fieldDrafts.{{ $index }}.label_fa" dir="rtl">
                    </label>
                </div>

                <label class="eg-admin-field mt-3">
                    <span>{{ __('admin.mission_engine.help_en') }}</span>
                    <input type="text" class="eg-admin-input" wire:model="fieldDrafts.{{ $index }}.help_en">
                </label>

                <details class="eg-admin-config-example mt-3">
                    <summary>{{ __('admin.mission_engine.field_json_help_title') }}</summary>
                    <p class="eg-admin-capability-card__hint mb-2">{{ __('admin.mission_engine.field_json_help') }}</p>
                    <pre class="eg-admin-config-example__code mb-3"><code>{{ __('admin.mission_engine.field_default_example') }}</code></pre>
                </details>

                <div class="eg-admin-field-row mt-3">
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.default_json') }}</span>
                        <textarea class="eg-admin-textarea eg-admin-textarea--mono" rows="4" wire:model="fieldDrafts.{{ $index }}.default_value_json"></textarea>
                    </label>
                    <label class="eg-admin-field">
                        <span>{{ __('admin.mission_engine.options_json') }}</span>
                        <textarea class="eg-admin-textarea eg-admin-textarea--mono" rows="4" wire:model="fieldDrafts.{{ $index }}.options_json"></textarea>
                    </label>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 pt-3 eg-admin-field-card__footer">
                    <label class="eg-admin-check mb-0">
                        <input type="checkbox" wire:model="fieldDrafts.{{ $index }}.is_required">
                        <span>{{ __('admin.mission_engine.field_required') }}</span>
                    </label>
                    <button type="button" class="eg-admin-btn eg-admin-btn--primary eg-admin-btn--sm" wire:click="saveField({{ $field['id'] }})">
                        {{ __('admin.mission_engine.save_field') }}
                    </button>
                </div>
            </article>
        @endforeach
    </div>
@endif
