<div>
    @include('partials.admin.page-head', ['title' => $item ? __('admin.gamification.edit_shop_item') : __('admin.gamification.new_shop_item'), 'subtitle' => __('admin.gamification.shop_subtitle'), 'backRoute' => 'admin.gamification.shop.index'])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])
    <form wire:submit="save" class="eg-admin-card p-4">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">{{ __('admin.gamification.col_slug') }}</label><input type="text" wire:model="slug" class="form-control eg-admin-input" @disabled($item !== null)></div>
            <div class="col-md-4"><label class="form-label">{{ __('admin.gamification.col_name') }}</label><input type="text" wire:model="name" class="form-control eg-admin-input"></div>
            <div class="col-md-4"><label class="form-label">{{ __('admin.gamification.col_cost') }}</label><input type="number" wire:model="costCoins" class="form-control eg-admin-input" min="0"></div>
            <div class="col-md-4"><label class="form-label">{{ __('admin.gamification.col_icon') }}</label><input type="text" wire:model="icon" class="form-control eg-admin-input"></div>
            <div class="col-md-4"><label class="form-label">{{ __('admin.gamification.col_effect') }}</label><select wire:model="effectType" class="form-select eg-admin-input">@foreach ($effectTypes as $t)<option value="{{ $t->value }}">{{ $t->value }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">{{ __('admin.gamification.col_sort') }}</label><input type="number" wire:model="sortOrder" class="form-control eg-admin-input" min="1"></div>
            <div class="col-12"><label class="form-label">{{ __('admin.gamification.description') }}</label><input type="text" wire:model="description" class="form-control eg-admin-input"></div>
            <div class="col-12"><label class="form-label">{{ __('admin.gamification.effects_json') }}</label><textarea wire:model="effectsJson" rows="6" class="form-control eg-admin-input font-monospace"></textarea>@error('effectsJson')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-check"><input type="checkbox" wire:model="isActive" class="form-check-input"><span class="form-check-label">{{ __('admin.gamification.col_active') }}</span></label></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.save') }}</button>
            @if ($item)<button type="button" class="eg-admin-btn eg-admin-btn--danger" wire:click="delete" wire:confirm="{{ __('admin.gamification.confirm_delete') }}">{{ __('admin.delete') }}</button>@endif
        </div>
    </form>
</div>
