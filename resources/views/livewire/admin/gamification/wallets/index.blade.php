<div>
    @include('partials.admin.page-head', ['title' => __('admin.gamification.wallets_title'), 'subtitle' => __('admin.gamification.wallets_subtitle'), 'backRoute' => null])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <form wire:submit="search" class="eg-admin-card p-4 mb-4">
        <label class="form-label">{{ __('admin.gamification.user_email') }}</label>
        <div class="d-flex gap-2">
            <input type="email" wire:model="email" class="form-control eg-admin-input">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.search') }}</button>
        </div>
        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </form>

    @if ($wallet)
        <div class="eg-admin-card p-4 mb-4">
            <p>{{ __('admin.gamification.col_points') }}: {{ eg_num($wallet['points']) }} · {{ __('admin.gamification.col_coins') }}: {{ eg_num($wallet['coins']) }} · Level {{ eg_num($wallet['level']) }}</p>
            <form wire:submit="adjust" class="row g-3">
                <div class="col-md-3"><label class="form-label">{{ __('admin.gamification.adjust_points') }}</label><input type="number" wire:model="pointsDelta" class="form-control eg-admin-input"></div>
                <div class="col-md-3"><label class="form-label">{{ __('admin.gamification.adjust_coins') }}</label><input type="number" wire:model="coinsDelta" class="form-control eg-admin-input"></div>
                <div class="col-md-3"><label class="form-label">{{ __('admin.gamification.adjust_xp') }}</label><input type="number" wire:model="xpDelta" class="form-control eg-admin-input"></div>
                <div class="col-12"><label class="form-label">{{ __('admin.gamification.adjust_reason') }}</label><input type="text" wire:model="reason" class="form-control eg-admin-input"></div>
                <div class="col-12"><button type="submit" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.gamification.apply_adjustment') }}</button></div>
            </form>
        </div>
    @endif
</div>
