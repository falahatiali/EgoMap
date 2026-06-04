<div>
    @include('partials.admin.page-head', [
        'title' => $rule ? __('admin.gamification.edit_rule') : __('admin.gamification.new_rule'),
        'subtitle' => __('admin.gamification.edit_subtitle'),
        'backRoute' => 'admin.gamification.rules.index',
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => 'rules'])

    <form wire:submit="save" class="eg-admin-card p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('admin.gamification.col_key') }}</label>
                <input type="text" wire:model="key" class="form-control eg-admin-input" @disabled($rule !== null)>
                @error('key') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('admin.gamification.col_name') }}</label>
                <input type="text" wire:model="name" class="form-control eg-admin-input">
                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('admin.gamification.description') }}</label>
                <input type="text" wire:model="description" class="form-control eg-admin-input">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('admin.gamification.col_event') }}</label>
                <select wire:model="event" class="form-select eg-admin-input">
                    @foreach ($events as $eventCase)
                        <option value="{{ $eventCase->value }}">{{ $eventCase->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('admin.gamification.col_type') }}</label>
                <select wire:model="ruleType" class="form-select eg-admin-input">
                    @foreach ($ruleTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('admin.gamification.col_priority') }}</label>
                <input type="number" wire:model="priority" class="form-control eg-admin-input" min="1">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('admin.gamification.max_per_day') }}</label>
                <input type="number" wire:model="maxPerDay" class="form-control eg-admin-input" min="1" placeholder="∞">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <label class="form-check mb-2">
                    <input type="checkbox" wire:model="isActive" class="form-check-input">
                    <span class="form-check-label">{{ __('admin.gamification.col_active') }}</span>
                </label>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('admin.gamification.conditions_json') }}</label>
                <textarea wire:model="conditionsJson" rows="8" class="form-control eg-admin-input font-monospace"></textarea>
                @error('conditionsJson') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('admin.gamification.effects_json') }}</label>
                <textarea wire:model="effectsJson" rows="8" class="form-control eg-admin-input font-monospace"></textarea>
                @error('effectsJson') <div class="text-danger small">{{ $message }}</div> @enderror
                <p class="small eg-text-muted mt-2">{{ __('admin.gamification.effects_hint') }}</p>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.save') }}</button>
        </div>
    </form>
</div>
