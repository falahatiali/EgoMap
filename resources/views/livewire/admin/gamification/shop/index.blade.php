<div>
    @include('partials.admin.page-head', ['title' => __('admin.gamification.shop_title'), 'subtitle' => __('admin.gamification.shop_subtitle'), 'backRoute' => null])
    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])
    <div class="mb-4"><a href="{{ route('admin.gamification.shop.create') }}" class="eg-admin-btn eg-admin-btn--primary">{{ __('admin.gamification.new_shop_item') }}</a></div>
    <div class="eg-admin-card table-responsive">
        <table class="table eg-admin-table mb-0">
            <thead><tr><th>{{ __('admin.gamification.col_slug') }}</th><th>{{ __('admin.gamification.col_name') }}</th><th>{{ __('admin.gamification.col_cost') }}</th><th>{{ __('admin.gamification.col_effect') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($items as $item)
                    <tr wire:key="shop-{{ $item->id }}">
                        <td><code>{{ $item->slug }}</code></td>
                        <td><i class="fa-solid {{ $item->icon }} me-1"></i>{{ $item->name }}</td>
                        <td>{{ eg_num($item->cost_coins) }}</td>
                        <td>{{ $item->effect_type->value }}</td>
                        <td><a href="{{ route('admin.gamification.shop.edit', $item) }}" class="eg-admin-btn eg-admin-btn--sm">{{ __('admin.edit') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 eg-text-muted">{{ __('admin.gamification.no_shop_items') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $items->links() }}</div>
    </div>
</div>
