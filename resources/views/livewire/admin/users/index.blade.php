<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.users.title'),
        'subtitle' => __('admin.users.subtitle'),
    ])

    <div class="eg-admin-toolbar">
        <input
            type="search"
            class="eg-admin-input"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin.users.search_placeholder') }}"
        >
        <select class="eg-admin-select" wire:model.live="roleFilter">
            <option value="">{{ __('admin.filters.all_roles') }}</option>
            @foreach ($roleOptions as $role)
                <option value="{{ $role }}">{{ $role }}</option>
            @endforeach
        </select>
    </div>

    <div class="eg-admin-panel">
        <div class="eg-admin-table-wrap">
            <table class="eg-admin-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.table.user') }}</th>
                        <th>{{ __('admin.table.roles') }}</th>
                        <th>{{ __('admin.users.verified') }}</th>
                        <th>{{ __('admin.table.joined') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <span class="eg-admin-table-primary">{{ $user->name }}</span>
                                <span class="eg-admin-table-muted">{{ $user->email }}</span>
                            </td>
                            <td>
                                @foreach ($user->roles as $role)
                                    <span class="eg-admin-tag">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if ($user->email_verified_at)
                                    <span class="eg-admin-status eg-admin-status--completed">{{ __('admin.users.yes') }}</span>
                                @else
                                    <span class="eg-admin-status eg-admin-status--muted">{{ __('admin.users.no') }}</span>
                                @endif
                            </td>
                            <td class="eg-admin-table-mono">{{ $user->created_at?->format('M j, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.actions.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="eg-admin-table-empty">{{ __('admin.table.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="eg-admin-pagination">{{ $users->links() }}</div>
        @endif
    </div>
</div>
