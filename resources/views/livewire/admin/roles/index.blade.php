<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.roles.title'),
        'subtitle' => __('admin.roles.subtitle'),
    ])

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.roles.name') }}</th>
                        <th>{{ __('admin.roles.permissions_count') }}</th>
                        <th>{{ __('admin.roles.users_count') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td><span class="eg-admin-table-primary">{{ $role->name }}</span></td>
                            <td>{{ number_format($role->permissions_count) }}</td>
                            <td>{{ number_format($role->users_count) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.roles.manage_permissions') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
