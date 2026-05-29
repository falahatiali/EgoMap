<div class="eg-mission-block">
    <h2 class="eg-mission-block-title">{{ __('missions.equipment_inventory') }}</h2>
    <p class="eg-text-muted small mb-3">{{ __('missions.equipment_inventory_help') }}</p>

    <p class="small fw-semibold mb-2">{{ __('missions.equipment_quick_add') }}</p>
    <div class="eg-mission-preset-chips mb-4">
        @foreach ($equipmentPresets as $preset)
            <button
                type="button"
                class="btn btn-sm btn-outline-light"
                wire:click="addEquipmentPreset('{{ $preset['key'] }}')"
                wire:loading.attr="disabled"
            >
                + {{ __($preset['name_key']) }}
            </button>
        @endforeach
    </div>

    <ul class="list-unstyled eg-mission-equipment-list mb-4">
        @forelse ($equipmentItems as $item)
            @php
                $category = \Modules\MissionEngine\Enums\EquipmentCategory::tryFrom($item['category'])
                    ?? \Modules\MissionEngine\Enums\EquipmentCategory::Other;
                $status = \Modules\MissionEngine\Enums\EquipmentStatus::tryFrom($item['status'])
                    ?? \Modules\MissionEngine\Enums\EquipmentStatus::Owned;
            @endphp
            <li class="eg-mission-equipment-card" wire:key="equipment-{{ $item['id'] }}">
                <div class="eg-mission-equipment-card-head">
                    <div>
                        <strong class="eg-mission-equipment-name">{{ $item['name'] }}</strong>
                        @if (filled($item['brand']))
                            <span class="eg-text-muted small"> — {{ $item['brand'] }}</span>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        wire:click="removeEquipmentItem('{{ $item['id'] }}')"
                        aria-label="{{ __('missions.equipment_remove') }}"
                    >
                        {{ __('missions.workout_remove') }}
                    </button>
                </div>
                <div class="eg-mission-equipment-meta">
                    <span class="eg-badge eg-mission-equipment-badge">{{ $category->label($locale) }}</span>
                    <span class="eg-badge eg-mission-equipment-badge eg-mission-equipment-badge--status">{{ $status->label($locale) }}</span>
                </div>
                @if (filled($item['notes']))
                    <p class="small eg-text-muted mb-0 mt-2">{{ $item['notes'] }}</p>
                @endif
            </li>
        @empty
            <li class="eg-mission-equipment-empty eg-text-muted small">{{ __('missions.equipment_empty') }}</li>
        @endforelse
    </ul>

    <h3 class="h6 mb-3">{{ __('missions.equipment_add_item') }}</h3>
    <form wire:submit="addEquipmentItem" class="eg-mission-inline-form mb-4">
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label class="form-label">{{ __('missions.equipment_name') }}</label>
                <input
                    type="text"
                    class="form-control @error('newEquipmentName') is-invalid @enderror"
                    wire:model="newEquipmentName"
                    placeholder="{{ __('missions.equipment_name_placeholder') }}"
                >
                @error('newEquipmentName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('missions.equipment_brand') }}</label>
                <input
                    type="text"
                    class="form-control"
                    wire:model="newEquipmentBrand"
                    placeholder="{{ __('missions.equipment_brand_placeholder') }}"
                >
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.equipment_category') }}</label>
                <select class="form-select" wire:model="newEquipmentCategory">
                    @foreach ($equipmentCategories as $category)
                        <option value="{{ $category->value }}">{{ $category->label($locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.equipment_status') }}</label>
                <select class="form-select" wire:model="newEquipmentStatus">
                    @foreach ($equipmentStatuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label($locale) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('missions.equipment_item_notes') }}</label>
                <input type="text" class="form-control" wire:model="newEquipmentNotes" placeholder="{{ __('missions.equipment_item_notes_placeholder') }}">
            </div>
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm">
            {{ __('missions.equipment_add_button') }}
        </button>
    </form>

    <h3 class="h6 mb-2">{{ __('missions.equipment_general_notes') }}</h3>
    <p class="eg-text-muted small mb-2">{{ __('missions.equipment_general_notes_help') }}</p>
    <form wire:submit="saveEquipment">
        <textarea
            class="form-control mb-3"
            rows="4"
            wire:model="equipmentNotes"
            placeholder="{{ __('missions.equipment_general_notes_placeholder') }}"
        ></textarea>
        <button type="submit" class="btn btn-primary">{{ __('missions.save') }}</button>
    </form>
</div>
