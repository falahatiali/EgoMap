<div>
    @include('partials.admin.page-head', [
        'title' => $punishment ? __('admin.gamification.edit_punishment') : __('admin.gamification.new_punishment'),
        'subtitle' => __('admin.gamification.punishments_subtitle'),
        'backRoute' => 'admin.gamification.punishments.index',
    ])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <form wire:submit="save" class="eg-admin-card p-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('admin.gamification.col_slug') }}</label>
                <input type="text" wire:model="slug" class="form-control eg-admin-input" @disabled($punishment !== null)>
            </div>
            <div class="col-md-8">
                <label class="form-label">{{ __('admin.gamification.col_name') }}</label>
                <input type="text" wire:model="title" class="form-control eg-admin-input">
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('admin.gamification.description') }}</label>
                <textarea wire:model="description" class="form-control eg-admin-input" rows="3"></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('admin.gamification.col_type') }}</label>
                <select wire:model="type" class="form-select eg-admin-input">
                    @foreach ($types as $t)
                        <option value="{{ $t->value }}">{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('admin.gamification.col_difficulty') }}</label>
                <select wire:model="difficulty" class="form-select eg-admin-input">
                    @foreach ($difficulties as $d)
                        <option value="{{ $d->value }}">{{ $d->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('admin.gamification.col_minutes') }}</label>
                <input type="number" wire:model="estimatedMinutes" class="form-control eg-admin-input" min="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('admin.gamification.min_slip_severity') }}</label>
                <input type="number" wire:model="minSlipSeverity" class="form-control eg-admin-input" min="1" max="3">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('admin.gamification.sort_order') }}</label>
                <input type="number" wire:model="sortOrder" class="form-control eg-admin-input">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('admin.gamification.col_points') }}</label>
                <input type="number" wire:model="points" class="form-control eg-admin-input">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('admin.gamification.col_coins') }}</label>
                <input type="number" wire:model="coins" class="form-control eg-admin-input">
            </div>
            <div class="col-12">
                <label class="form-check">
                    <input type="checkbox" wire:model="isActive" class="form-check-input">
                    <span class="form-check-label">{{ __('admin.gamification.col_active') }}</span>
                </label>
            </div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.save') }}</button>
            @if ($punishment)
                <button type="button" class="eg-admin-btn eg-admin-btn--danger" wire:click="delete" wire:confirm="{{ __('admin.gamification.confirm_delete') }}">
                    {{ __('admin.delete') }}
                </button>
            @endif
        </div>
    </form>
</div>
