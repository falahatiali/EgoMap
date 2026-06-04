<div>
    @include('partials.admin.page-head', [
        'title' => __('admin.gamification.punishments_title'),
        'subtitle' => __('admin.gamification.punishments_subtitle'),
        'backRoute' => null,
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <div class="d-flex flex-wrap gap-2 mb-4">
        <input type="search" wire:model.live.debounce.300ms="search" class="form-control" style="max-width: 16rem" placeholder="{{ __('admin.search') }}">
        <a href="{{ route('admin.gamification.punishments.create') }}" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.gamification.new_punishment') }}</a>
    </div>

    <div class="eg-admin-card">
        <div class="table-responsive">
            <table class="table eg-admin-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.gamification.col_slug') }}</th>
                        <th>{{ __('admin.gamification.col_name') }}</th>
                        <th>{{ __('admin.gamification.col_type') }}</th>
                        <th>{{ __('admin.gamification.col_difficulty') }}</th>
                        <th>{{ __('admin.gamification.col_minutes') }}</th>
                        <th>{{ __('admin.gamification.col_active') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($punishments as $punishment)
                        <tr wire:key="punishment-{{ $punishment->id }}">
                            <td><code>{{ $punishment->slug }}</code></td>
                            <td>{{ $punishment->title }}</td>
                            <td>{{ $punishment->type->value }}</td>
                            <td>{{ $punishment->difficulty->value }}</td>
                            <td>{{ eg_num($punishment->estimated_minutes) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link p-0" wire:click="toggleActive({{ $punishment->id }})">
                                    {{ $punishment->is_active ? __('admin.active') : __('admin.inactive') }}
                                </button>
                            </td>
                            <td>
                                <a href="{{ route('admin.gamification.punishments.edit', $punishment) }}" class="eg-admin-btn eg-admin-btn--sm">{{ __('admin.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 eg-text-muted">{{ __('admin.gamification.no_punishments') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $punishments->links() }}</div>
    </div>
</div>
