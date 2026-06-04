<div>
    @include('partials.admin.page-head', [
        'title' => __('admin.gamification.badges_title'),
        'subtitle' => __('admin.gamification.badges_subtitle'),
        'backRoute' => null,
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <div class="mb-4">
        <a href="{{ route('admin.gamification.badges.create') }}" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.gamification.new_badge') }}</a>
    </div>

    <div class="eg-admin-card">
        <div class="table-responsive">
            <table class="table eg-admin-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.gamification.col_slug') }}</th>
                        <th>{{ __('admin.gamification.col_name') }}</th>
                        <th>{{ __('admin.gamification.col_icon') }}</th>
                        <th>{{ __('admin.gamification.col_earned') }}</th>
                        <th>{{ __('admin.gamification.col_active') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($badges as $badge)
                        <tr wire:key="badge-{{ $badge->id }}">
                            <td><code>{{ $badge->slug }}</code></td>
                            <td>{{ $badge->name }}</td>
                            <td><i class="fa-solid {{ $badge->icon }}"></i></td>
                            <td>{{ eg_num($earnedCounts[$badge->slug] ?? 0) }}</td>
                            <td>{{ $badge->is_active ? __('admin.active') : __('admin.inactive') }}</td>
                            <td>
                                <a href="{{ route('admin.gamification.badges.edit', $badge) }}" class="eg-admin-btn eg-admin-btn--sm">{{ __('admin.edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 eg-text-muted">{{ __('admin.gamification.no_badges') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $badges->links() }}</div>
    </div>
</div>
