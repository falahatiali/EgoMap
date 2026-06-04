<form wire:submit="saveCapabilities" class="eg-admin-form-grid">
    <div class="eg-admin-panel eg-admin-panel--padded">
        @include('livewire.admin.mission-engine.templates.partials.section-lead', [
            'title' => __('admin.mission_engine.capabilities'),
            'description' => __('admin.mission_engine.capabilities_intro'),
            'icon' => 'fa-puzzle-piece',
        ])

        <div class="eg-admin-capability-grid">
            @foreach ($capabilityTypes as $capability)
                @php
                    $isEnabled = in_array($capability->id, $enabledCapabilityIds, true);
                    $configHint = \Modules\MissionEngine\Support\MissionCapabilityConfigExamples::hintFor($capability->key);
                    $configExample = \Modules\MissionEngine\Support\MissionCapabilityConfigExamples::jsonFor($capability->key);
                @endphp
                <article class="eg-admin-capability-card @if ($isEnabled) is-enabled @endif" wire:key="cap-card-{{ $capability->id }}">
                    <label class="eg-admin-capability-card__toggle">
                        <input
                            type="checkbox"
                            class="eg-admin-capability-card__checkbox"
                            value="{{ $capability->id }}"
                            wire:model.live="enabledCapabilityIds"
                        >
                        <span class="eg-admin-capability-card__icon" aria-hidden="true">
                            <i class="fa-solid {{ $capability->icon }}"></i>
                        </span>
                        <span class="eg-admin-capability-card__copy">
                            <strong>{{ $capability->getTranslation('name', 'en', true) }}</strong>
                            <span class="eg-admin-capability-card__key">{{ $capability->key->value }}</span>
                        </span>
                    </label>

                    @if ($isEnabled)
                        <div class="eg-admin-capability-card__config">
                            <p class="eg-admin-capability-card__hint">
                                <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                                {{ $configHint }}
                            </p>

                            <label class="eg-admin-field mb-0">
                                <span class="eg-admin-field-label-sm">{{ __('admin.mission_engine.capability_config_json') }}</span>
                                <textarea
                                    class="eg-admin-textarea eg-admin-textarea--mono"
                                    rows="8"
                                    wire:model="capabilityConfigJson.{{ $capability->id }}"
                                    placeholder="{{ __('admin.mission_engine.config_json_placeholder') }}"
                                ></textarea>
                                @error('capabilityConfigJson.'.$capability->id)
                                    <span class="eg-admin-error">{{ $message }}</span>
                                @enderror
                            </label>

                            @if ($configExample)
                                <details class="eg-admin-config-example">
                                    <summary>{{ __('admin.mission_engine.config_example_show') }}</summary>
                                    <pre class="eg-admin-config-example__code"><code>{{ $configExample }}</code></pre>
                                    <button
                                        type="button"
                                        class="eg-admin-btn eg-admin-btn--sm mt-2"
                                        wire:click="fillCapabilityConfigExample({{ $capability->id }}, @js($capability->key->value))"
                                    >
                                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                                        {{ __('admin.mission_engine.config_example_insert') }}
                                    </button>
                                </details>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
        @error('enabledCapabilityIds') <span class="eg-admin-error d-block mt-3">{{ $message }}</span> @enderror
    </div>

    <div class="eg-admin-form-actions">
        <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="saveCapabilities">{{ __('admin.mission_engine.save_capabilities') }}</span>
            <span wire:loading wire:target="saveCapabilities">{{ __('admin.actions.saving') }}</span>
        </button>
    </div>
</form>
