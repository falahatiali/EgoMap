<div>
    @include('partials.admin.page-head', [
        'title' => __('admin.gamification.transactions_title'),
        'subtitle' => __('admin.gamification.transactions_subtitle'),
        'backRoute' => null,
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => $activeGamificationNav])

    <div class="eg-admin-toolbar mb-4">
        <input type="search" wire:model.live.debounce.300ms="search" class="form-control eg-admin-input" placeholder="{{ __('admin.search') }}">
        <select wire:model.live="eventFilter" class="form-select eg-admin-input">
            <option value="">{{ __('admin.gamification.all_events') }}</option>
            @foreach ($events as $event)
                <option value="{{ $event }}">{{ $event }}</option>
            @endforeach
        </select>
    </div>

    <div class="eg-admin-card">
        <div class="table-responsive">
            <table class="table eg-admin-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.gamification.col_when') }}</th>
                        <th>{{ __('admin.gamification.col_user') }}</th>
                        <th>{{ __('admin.gamification.col_event') }}</th>
                        <th>{{ __('admin.gamification.col_rule') }}</th>
                        <th>{{ __('admin.gamification.col_points') }}</th>
                        <th>{{ __('admin.gamification.col_coins') }}</th>
                        <th>XP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr wire:key="tx-{{ $tx->id }}">
                            <td class="small">{{ $tx->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="small">{{ $tx->wallet?->user?->email ?? __('admin.gamification.guest_wallet') }}</td>
                            <td><code class="small">{{ $tx->event }}</code></td>
                            <td class="small">
                                @if ($tx->rule)
                                    <span @class(['text-success' => $tx->rule->rule_type->value === 'reward', 'text-danger' => $tx->rule->rule_type->value === 'penalty'])>
                                        {{ $tx->rule->key }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td @class(['text-success' => $tx->points_delta > 0, 'text-danger' => $tx->points_delta < 0])>{{ $tx->points_delta > 0 ? '+' : '' }}{{ $tx->points_delta }}</td>
                            <td>{{ $tx->coins_delta > 0 ? '+' : '' }}{{ $tx->coins_delta }}</td>
                            <td>{{ $tx->xp_delta > 0 ? '+' : '' }}{{ $tx->xp_delta }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center eg-text-muted py-4">{{ __('admin.gamification.no_transactions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
</div>
