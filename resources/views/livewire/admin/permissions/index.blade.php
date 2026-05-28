<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.permissions.title'),
        'subtitle' => __('admin.permissions.subtitle'),
    ])

    <div class="eg-admin-toolbar">
        <input
            type="search"
            class="eg-admin-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.permissions.search_placeholder') }}"
        >
    </div>

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.permissions.name') }}</th>
                        <th>{{ __('admin.permissions.group') }}</th>
                        @foreach ($roles as $role)
                            <th class="text-center">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        @php
                            $group = explode('.', $permission->name)[0];
                            $roleNames = $roles
                                ->filter(fn ($role) => $role->permissions->contains('name', $permission->name))
                                ->pluck('name');
                        @endphp
                        <tr wire:key="perm-{{ $permission->id }}">
                            <td class="eg-admin-table-mono">{{ $permission->name }}</td>
                            <td><span class="eg-admin-tag">{{ $group }}</span></td>
                            @foreach ($roles as $role)
                                <td class="text-center">
                                    @if ($roleNames->contains($role->name))
                                        <i class="fa-solid fa-check text-success" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ __('admin.permissions.granted') }}</span>
                                    @else
                                        <span class="eg-admin-table-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $roles->count() }}" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="eg-admin-page-sub mt-3">{{ __('admin.permissions.edit_via_roles') }}</p>
</div>
