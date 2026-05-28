<div class="eg-admin-page">
    @include('partials.admin.page-head', [
        'title' => __('admin.users.edit_title'),
        'subtitle' => $user->email,
        'backRoute' => 'admin.users.index',
        'backLabel' => __('admin.users.back_to_list'),
    ])

    <form wire:submit="save" class="eg-admin-form-grid">
        <div class="eg-admin-panel eg-admin-panel--padded">
            <h3 class="eg-admin-panel-title">{{ __('admin.users.account') }}</h3>

            <label class="eg-admin-field">
                <span>{{ __('admin.users.name') }}</span>
                <input type="text" class="eg-admin-input" wire:model="name">
                @error('name') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <label class="eg-admin-field">
                <span>{{ __('admin.users.email') }}</span>
                <input type="email" class="eg-admin-input" wire:model="email">
                @error('email') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>

            <div class="eg-admin-field-spacer">
                <label class="eg-admin-check">
                    <input type="checkbox" wire:model="emailVerified">
                    <span>{{ __('admin.users.mark_verified') }}</span>
                </label>
            </div>

            <label class="eg-admin-field">
                <span>{{ __('admin.users.new_password') }}</span>
                <input type="password" class="eg-admin-input" wire:model="password" autocomplete="new-password" placeholder="{{ __('admin.users.password_optional') }}">
                @error('password') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </label>
        </div>

        @if ($canManageRoles)
            <div class="eg-admin-panel eg-admin-panel--padded">
                <h3 class="eg-admin-panel-title">{{ __('admin.users.roles') }}</h3>
                <div class="eg-admin-check-grid eg-admin-check-grid--stack">
                    @foreach ($roleOptions as $role)
                        <label class="eg-admin-check">
                            <input type="checkbox" value="{{ $role }}" wire:model="selectedRoles">
                            <span>{{ $role }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selectedRoles') <span class="eg-admin-error">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="eg-admin-form-actions">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('admin.actions.save') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.actions.saving') }}</span>
            </button>
        </div>
    </form>

    @if ($canDelete)
        <div class="eg-admin-panel eg-admin-panel--padded eg-admin-panel--danger">
            <h3 class="eg-admin-panel-title">{{ __('admin.users.danger_zone') }}</h3>
            <p class="eg-admin-page-sub">{{ __('admin.users.delete_help') }}</p>
            @if ($confirmDelete)
                <p class="eg-admin-error mb-3">{{ __('admin.users.delete_confirm') }}</p>
                <div class="d-flex gap-2">
                    <button type="button" class="eg-admin-btn eg-admin-btn--danger" wire:click="delete">
                        {{ __('admin.users.delete_yes') }}
                    </button>
                    <button type="button" class="eg-admin-btn" wire:click="cancelDelete">
                        {{ __('admin.actions.cancel') }}
                    </button>
                </div>
            @else
                <button type="button" class="eg-admin-btn eg-admin-btn--danger" wire:click="delete">
                    {{ __('admin.users.delete') }}
                </button>
            @endif
        </div>
    @endif
</div>
