<div>
    @include('partials.admin.page-head', [
        'title' => $badge ? __('admin.gamification.edit_badge') : __('admin.gamification.new_badge'),
        'subtitle' => __('admin.gamification.badges_subtitle'),
        'backRoute' => 'admin.gamification.badges.index',
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <form wire:submit="save" class="eg-admin-card p-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('admin.gamification.col_slug') }}</label>
                <input type="text" wire:model="slug" class="form-control eg-admin-input" @disabled($badge !== null)>
                @error('slug') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('admin.gamification.col_name') }}</label>
                <input type="text" wire:model="name" class="form-control eg-admin-input">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('admin.gamification.col_icon') }}</label>
                <input type="text" wire:model="icon" class="form-control eg-admin-input" placeholder="fa-medal">
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('admin.gamification.description') }}</label>
                <input type="text" wire:model="description" class="form-control eg-admin-input">
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
            @if ($badge)
                <button type="button" class="eg-admin-btn eg-admin-btn--danger" wire:click="delete" wire:confirm="{{ __('admin.gamification.confirm_delete') }}">{{ __('admin.delete') }}</button>
            @endif
        </div>
    </form>
</div>
