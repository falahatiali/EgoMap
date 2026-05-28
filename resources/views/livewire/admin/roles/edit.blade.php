<div class="eg-admin-page">
    @include('partials.admin.page-head', [
        'title' => __('admin.roles.edit_title', ['role' => $role->name]),
        'subtitle' => __('admin.roles.users_assigned', ['count' => $usersCount]),
        'backRoute' => 'admin.roles.index',
        'backLabel' => __('admin.roles.back_to_list'),
    ])

    <form wire:submit="save">
        @foreach ($groups as $group => $permissions)
            <div class="eg-admin-panel eg-admin-panel--padded mb-3">
                <h3 class="eg-admin-panel-title">{{ str($group)->replace('-', ' ')->title() }}</h3>
                <div class="eg-admin-check-grid">
                    @foreach ($permissions as $permission)
                        <label class="eg-admin-check">
                            <input
                                type="checkbox"
                                value="{{ $permission['name'] }}"
                                wire:model="selectedPermissions"
                            >
                            <span>{{ $permission['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="eg-admin-form-actions">
            <button type="submit" class="eg-admin-btn eg-admin-btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('admin.actions.save_permissions') }}</span>
                <span wire:loading wire:target="save">{{ __('admin.actions.saving') }}</span>
            </button>
        </div>
    </form>
</div>
