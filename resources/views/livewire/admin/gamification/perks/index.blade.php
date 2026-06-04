<div>
    @include('partials.admin.page-head', ['title' => __('admin.gamification.perks_title'), 'subtitle' => __('admin.gamification.perks_subtitle'), 'backRoute' => null])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])
    <div class="mb-4"><a href="{{ route('admin.gamification.perks.create') }}" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.gamification.new_perk') }}</a></div>
    <div class="eg-admin-card table-responsive">
        <table class="table eg-admin-table mb-0">
            <thead><tr><th>{{ __('admin.gamification.col_slug') }}</th><th>{{ __('admin.gamification.col_name') }}</th><th>{{ __('admin.gamification.col_type') }}</th><th>{{ __('admin.gamification.col_active') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($perks as $perk)
                    <tr wire:key="perk-{{ $perk->id }}">
                        <td><code>{{ $perk->slug }}</code></td>
                        <td>{{ $perk->name }}</td>
                        <td>{{ $perk->type->value }}</td>
                        <td>{{ $perk->is_active ? __('admin.active') : __('admin.inactive') }}</td>
                        <td><a href="{{ route('admin.gamification.perks.edit', $perk) }}" class="eg-admin-btn eg-admin-btn--sm">{{ __('admin.edit') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 eg-text-muted">{{ __('admin.gamification.no_perks') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $perks->links() }}</div>
    </div>
</div>
